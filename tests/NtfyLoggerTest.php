<?php

namespace Logger\Ntfy\Tests;

use Logger\Ntfy\NtfyClient;
use Logger\Ntfy\NtfyConfiguration;
use Logger\Ntfy\NtfyLogger;
use Logger\Ntfy\NtfyParams;
use Logger\Ntfy\Tests\Support\CapturingHttpClient;
use Logger\Ntfy\Tests\Support\SimpleRequestFactory;
use Logger\Ntfy\Tests\Support\SimpleStreamFactory;
use PHPUnit\Framework\TestCase;

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

	private function createLogger(CapturingHttpClient $httpClient): NtfyLogger {
		return new NtfyLogger(new NtfyClient(
			client: $httpClient,
			requestFactory: new SimpleRequestFactory(),
			streamFactory: new SimpleStreamFactory(),
			config: new NtfyConfiguration(topic: 'alerts', serverUrl: 'https://ntfy.example.com'),
		));
	}

	private static function assertSentRequest(CapturingHttpClient $httpClient): \Psr\Http\Message\RequestInterface {
		self::assertNotNull($httpClient->request);

		return $httpClient->request;
	}
}
