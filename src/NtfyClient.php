<?php

namespace Logger\Ntfy;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;

class NtfyClient {
	public function __construct(
		private ClientInterface $client,
		private RequestFactoryInterface $requestFactory,
		private StreamFactoryInterface $streamFactory,
		private NtfyConfiguration $config
	) {}

	public function getConfiguration(): NtfyConfiguration {
		return $this->config;
	}

	public function sendMessage(string $message, ?NtfyParams $params = null): void {
		$this->publish($message, $params);
	}

	public function sendMessageWithAttachment(string $message, string $attachment, string $filename, ?NtfyParams $params = null): void {
		$params = clone ($params ?? new NtfyParams());
		$params->filename = $filename;

		$this->publish($attachment, $params, queryParams: ['m' => $message]);
	}

	/**
	 * @param array<string, string> $headers
	 * @param array<string, string> $queryParams
	 */
	private function publish(string $body, ?NtfyParams $params = null, array $headers = [], array $queryParams = []): void {
		$params ??= new NtfyParams();
		$headers = array_merge(
			['Content-Type' => 'text/plain; charset=utf-8'],
			$this->config->getAuthenticationHeaders(),
			$params->toHeaders(),
			$headers,
		);
		$url = $this->appendQueryParams($this->config->getPublishUrl(topic: $params->topic, sequenceId: $params->sequenceId), $queryParams);
		$request = $this->requestFactory
			->createRequest('POST', $url)
			->withBody($this->streamFactory->createStream($body));

		foreach($headers as $name => $value) {
			$request = $request->withHeader($name, $value);
		}

		$response = $this->client->sendRequest($request);
		$statusCode = $response->getStatusCode();

		if($statusCode < 200 || $statusCode >= 300) {
			$responseBody = trim((string)$response->getBody());
			$details = $responseBody !== '' ? ": {$responseBody}" : '';

			throw new RuntimeException("Could not publish ntfy message to {$url} (HTTP {$statusCode}){$details}");
		}
	}

	/**
	 * @param array<string, string> $queryParams
	 */
	private function appendQueryParams(string $url, array $queryParams): string {
		if($queryParams === []) {
			return $url;
		}

		$query = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
		return $url.(str_contains($url, '?') ? '&' : '?').$query;
	}
}
