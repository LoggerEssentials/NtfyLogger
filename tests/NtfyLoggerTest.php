<?php

namespace Logger\Ntfy\Tests;

use Logger\Ntfy\NtfyClient;
use Logger\Ntfy\NtfyConfiguration;
use Logger\Ntfy\NtfyExceptionConfiguration;
use Logger\Ntfy\NtfyLogger;
use Logger\Ntfy\NtfyParams;
use Logger\Ntfy\Tests\Support\CapturingHttpClient;
use Logger\Ntfy\Tests\Support\SimpleRequestFactory;
use Logger\Ntfy\Tests\Support\SimpleStreamFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

class NtfyLoggerTest extends TestCase {
	public function testInterpolatesMessageAndMapsPsrLevelToDefaultNtfyParameters(): void {
		$httpClient = new CapturingHttpClient();
		$logger = $this->createLogger($httpClient);

		$logger->error('Order {orderId} failed for {customer}', [
			'orderId' => 123,
			'customer' => 'ACME',
		]);

		$request = self::assertSentRequest($httpClient);
		self::assertSame('Order 123 failed for ACME', (string)$request->getBody());
		self::assertSame('ERROR', $request->getHeaderLine('Title'));
		self::assertSame('4', $request->getHeaderLine('Priority'));
		self::assertSame('warning', $request->getHeaderLine('Tags'));
	}

	public function testUsesContextNtfyArrayInsteadOfDefaultLevelMapping(): void {
		$httpClient = new CapturingHttpClient();
		$logger = $this->createLogger($httpClient);

		$logger->warning('Queue depth high', [
			'ntfy' => [
				'title' => 'Queue',
				'priority' => 5,
				'tags' => ['rotating_light'],
				'topic' => 'ops',
				'sequence_id' => 'queue-depth',
			],
		]);

		$request = self::assertSentRequest($httpClient);
		self::assertSame('https://ntfy.example.com/ops/queue-depth', (string)$request->getUri());
		self::assertSame('Queue', $request->getHeaderLine('Title'));
		self::assertSame('5', $request->getHeaderLine('Priority'));
		self::assertSame('rotating_light', $request->getHeaderLine('Tags'));
	}

	public function testUsesContextNtfyParamsInstance(): void {
		$httpClient = new CapturingHttpClient();
		$logger = $this->createLogger($httpClient);

		$logger->info('Info message', [
			'title' => 'Ignored',
			'ntfy' => new NtfyParams(title: 'Explicit', priority: 1, tags: ['memo']),
		]);

		$request = self::assertSentRequest($httpClient);
		self::assertSame('Explicit', $request->getHeaderLine('Title'));
		self::assertSame('1', $request->getHeaderLine('Priority'));
		self::assertSame('memo', $request->getHeaderLine('Tags'));
	}

	public function testUsesContextUrlAsNotificationClickUrl(): void {
		$httpClient = new CapturingHttpClient();
		$logger = $this->createLogger($httpClient);

		$logger->info('Info message', [
			'url' => 'https://status.example.com/orders/123',
		]);

		$request = self::assertSentRequest($httpClient);
		self::assertSame('https://status.example.com/orders/123', $request->getHeaderLine('Click'));
	}

	public function testPrefersContextNtfyUrlOverUrl(): void {
		$httpClient = new CapturingHttpClient();
		$logger = $this->createLogger($httpClient);

		$logger->info('Info message', [
			'url' => 'https://example.com/generic',
			'ntfy_url' => 'https://status.example.com/specific',
		]);

		$request = self::assertSentRequest($httpClient);
		self::assertSame('https://status.example.com/specific', $request->getHeaderLine('Click'));
	}

	public function testAppendsThrowableContextAsReadableMarkdown(): void {
		$httpClient = new CapturingHttpClient();
		$logger = $this->createLogger($httpClient, new NtfyConfiguration(
			topic: 'alerts',
			serverUrl: 'https://ntfy.example.com',
			exceptionConfiguration: new NtfyExceptionConfiguration(
				basePath: dirname(__DIR__),
				applicationPaths: ['tests'],
			),
		));
		$exception = $this->createTestException();

		$logger->error('Import failed', [
			'exception' => $exception,
		]);

		$request = self::assertSentRequest($httpClient);
		$body = (string)$request->getBody();
		self::assertStringStartsWith('Import failed', $body);
		self::assertStringContainsString("**Context**\nCLI: `", $body);
		self::assertGreaterThan(
			strpos($body, '### Exception: `RuntimeException`'),
			strpos($body, '**Context**'),
		);
		self::assertStringContainsString('### Exception: `RuntimeException`', $body);
		self::assertStringContainsString('`Broken import`', $body);
		self::assertMatchesRegularExpression('~\*\*`tests/NtfyLoggerTest.php:\d+`\*\*~', $body);
		self::assertStringContainsString('**Trace**', $body);
		self::assertSame('yes', $request->getHeaderLine('Markdown'));
	}

	public function testRendersInnerMostExceptionFirst(): void {
		$httpClient = new CapturingHttpClient();
		$logger = $this->createLogger($httpClient);
		$exception = new RuntimeException('Outer failure', previous: new RuntimeException('Inner failure'));

		$logger->error('Import failed', [
			'exception' => $exception,
		]);

		$body = (string)self::assertSentRequest($httpClient)->getBody();
		self::assertStringContainsString('### Exception: `RuntimeException`', $body);
		self::assertStringContainsString('`Inner failure`', $body);
		self::assertStringNotContainsString('`Outer failure`', $body);
		self::assertStringNotContainsString('### Following exception:', $body);
	}

	public function testSendsShortenedMessageAndFullStacktraceAttachmentWhenExceptionMessageExceedsNtfyLimit(): void {
		$httpClient = new CapturingHttpClient();
		$logger = $this->createLogger($httpClient);
		$exception = new RuntimeException(str_repeat('inner failure ', 500), previous: new RuntimeException(str_repeat('root cause ', 500)));

		$logger->error(str_repeat('Import failed ', 500), [
			'exception' => $exception,
		]);

		$request = self::assertSentRequest($httpClient);
		parse_str(parse_url((string)$request->getUri(), PHP_URL_QUERY) ?: '', $query);
		$visibleMessage = is_string($query['m'] ?? null) ? $query['m'] : '';
		self::assertNotSame('', $visibleMessage);
		self::assertLessThanOrEqual(4096, strlen($visibleMessage));
		self::assertSame('', $request->getHeaderLine('Message'));
		self::assertSame('stacktrace.txt', $request->getHeaderLine('Filename'));
		self::assertStringContainsString('root cause', $visibleMessage);
		self::assertStringContainsString('... truncated ...', $visibleMessage);
		self::assertStringContainsString('inner failure', (string)$request->getBody());
		self::assertStringContainsString('root cause', (string)$request->getBody());
		self::assertSame('yes', $request->getHeaderLine('Markdown'));
	}

	public function testIgnoresNonThrowableExceptionContext(): void {
		$httpClient = new CapturingHttpClient();
		$logger = $this->createLogger($httpClient);

		$logger->error('Import failed', [
			'exception' => 'not throwable',
		]);

		$request = self::assertSentRequest($httpClient);
		self::assertSame('Import failed', (string)$request->getBody());
		self::assertSame('', $request->getHeaderLine('Markdown'));
	}

	private function createLogger(CapturingHttpClient $httpClient, ?NtfyConfiguration $configuration = null): NtfyLogger {
		return new NtfyLogger(new NtfyClient(
			client: $httpClient,
			requestFactory: new SimpleRequestFactory(),
			streamFactory: new SimpleStreamFactory(),
			config: $configuration ?? new NtfyConfiguration(topic: 'alerts', serverUrl: 'https://ntfy.example.com'),
		));
	}

	private function createTestException(): Throwable {
		try {
			$this->throwTestException();
		} catch(Throwable $exception) {
			return $exception;
		}

		throw new RuntimeException('Expected test exception was not thrown.');
	}

	private function throwTestException(): void {
		throw new RuntimeException('Broken import');
	}

	private static function assertSentRequest(CapturingHttpClient $httpClient): \Psr\Http\Message\RequestInterface {
		self::assertNotNull($httpClient->request);

		return $httpClient->request;
	}
}
