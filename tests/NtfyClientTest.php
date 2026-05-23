<?php

namespace Logger\Ntfy\Tests;

use Logger\Ntfy\NtfyClient;
use Logger\Ntfy\NtfyConfiguration;
use Logger\Ntfy\NtfyParams;
use Logger\Ntfy\Tests\Support\CapturingHttpClient;
use Logger\Ntfy\Tests\Support\SimpleRequestFactory;
use Logger\Ntfy\Tests\Support\SimpleResponse;
use Logger\Ntfy\Tests\Support\SimpleStreamFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class NtfyClientTest extends TestCase {
	public function testSendsMessageWithAuthenticationAndHeaders(): void {
		$httpClient = new CapturingHttpClient();
		$client = $this->createClient($httpClient, new NtfyConfiguration(
			topic: 'default',
			token: 'tk_secret',
			serverUrl: 'https://ntfy.example.com',
		));

		$client->sendMessage('Deployment finished', new NtfyParams(
			title: 'Deploy',
			priority: 3,
			tags: ['rocket'],
			topic: 'deployments',
			sequenceId: 'prod',
		));

		$request = self::assertSentRequest($httpClient);
		self::assertSame('POST', $request->getMethod());
		self::assertSame('https://ntfy.example.com/deployments/prod', (string)$request->getUri());
		self::assertSame('Deployment finished', (string)$request->getBody());
		self::assertSame('text/plain; charset=utf-8', $request->getHeaderLine('Content-Type'));
		self::assertSame('Bearer tk_secret', $request->getHeaderLine('Authorization'));
		self::assertSame('Deploy', $request->getHeaderLine('Title'));
		self::assertSame('3', $request->getHeaderLine('Priority'));
		self::assertSame('rocket', $request->getHeaderLine('Tags'));
	}

	public function testThrowsOnNonSuccessfulResponse(): void {
		$client = $this->createClient(
			new CapturingHttpClient(new SimpleResponse(500, 'server exploded')),
			new NtfyConfiguration(topic: 'alerts', serverUrl: 'https://ntfy.example.com'),
		);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Could not publish ntfy message to https://ntfy.example.com/alerts (HTTP 500): server exploded');

		$client->sendMessage('Failure');
	}

	public function testSendsLocalAttachmentWithVisibleMessageHeader(): void {
		$httpClient = new CapturingHttpClient();
		$client = $this->createClient($httpClient, new NtfyConfiguration(
			topic: 'alerts',
			serverUrl: 'https://ntfy.example.com',
		));

		$client->sendMessageWithAttachment('Import failed', 'Full stacktrace', 'stacktrace.txt', new NtfyParams(
			title: 'ERROR',
			markdown: true,
		));

		$request = self::assertSentRequest($httpClient);
		self::assertSame('Full stacktrace', (string)$request->getBody());
		self::assertSame('m=Import%20failed', parse_url((string)$request->getUri(), PHP_URL_QUERY) ?: '');
		self::assertSame('', $request->getHeaderLine('Message'));
		self::assertSame('stacktrace.txt', $request->getHeaderLine('Filename'));
		self::assertSame('yes', $request->getHeaderLine('Markdown'));
	}

	private function createClient(CapturingHttpClient $httpClient, NtfyConfiguration $configuration): NtfyClient {
		return new NtfyClient(
			client: $httpClient,
			requestFactory: new SimpleRequestFactory(),
			streamFactory: new SimpleStreamFactory(),
			config: $configuration,
		);
	}

	private static function assertSentRequest(CapturingHttpClient $httpClient): \Psr\Http\Message\RequestInterface {
		self::assertNotNull($httpClient->request);

		return $httpClient->request;
	}
}
