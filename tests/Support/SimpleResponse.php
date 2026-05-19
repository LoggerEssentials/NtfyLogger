<?php

namespace Logger\Ntfy\Tests\Support;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class SimpleResponse implements ResponseInterface {
	private StreamInterface $body;

	/** @var array<string, list<string>> */
	private array $headers = [];

	public function __construct(
		private int $statusCode = 200,
		string $body = '',
		private string $reasonPhrase = '',
		private string $protocolVersion = '1.1',
	) {
		$this->body = new SimpleStream($body);
	}

	public function getStatusCode(): int {
		return $this->statusCode;
	}

	public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface {
		$clone = clone $this;
		$clone->statusCode = $code;
		$clone->reasonPhrase = $reasonPhrase;

		return $clone;
	}

	public function getReasonPhrase(): string {
		return $this->reasonPhrase;
	}

	public function getProtocolVersion(): string {
		return $this->protocolVersion;
	}

	public function withProtocolVersion(string $version): ResponseInterface {
		$clone = clone $this;
		$clone->protocolVersion = $version;

		return $clone;
	}

	public function getHeaders(): array {
		return $this->headers;
	}

	public function hasHeader(string $name): bool {
		return isset($this->headers[$name]);
	}

	public function getHeader(string $name): array {
		return $this->headers[$name] ?? [];
	}

	public function getHeaderLine(string $name): string {
		return implode(', ', $this->getHeader($name));
	}

	public function withHeader(string $name, $value): ResponseInterface {
		$clone = clone $this;
		$clone->headers[$name] = array_values(array_map('strval', is_array($value) ? $value : [$value]));

		return $clone;
	}

	public function withAddedHeader(string $name, $value): ResponseInterface {
		$clone = clone $this;
		$clone->headers[$name] = [...($clone->headers[$name] ?? []), ...array_values(array_map('strval', is_array($value) ? $value : [$value]))];

		return $clone;
	}

	public function withoutHeader(string $name): ResponseInterface {
		$clone = clone $this;
		unset($clone->headers[$name]);

		return $clone;
	}

	public function getBody(): StreamInterface {
		return $this->body;
	}

	public function withBody(StreamInterface $body): ResponseInterface {
		$clone = clone $this;
		$clone->body = $body;

		return $clone;
	}
}
