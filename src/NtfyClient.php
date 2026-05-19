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

	public function sendMessage(string $message, ?NtfyParams $params = null): void {
		$params ??= new NtfyParams();
		$headers = array_merge(
			['Content-Type' => 'text/plain; charset=utf-8'],
			$this->config->getAuthenticationHeaders(),
			$params->toHeaders(),
		);
		$url = $this->config->getPublishUrl(topic: $params->topic, sequenceId: $params->sequenceId);
		$request = $this->requestFactory
			->createRequest('POST', $url)
			->withBody($this->streamFactory->createStream($message));

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
}
