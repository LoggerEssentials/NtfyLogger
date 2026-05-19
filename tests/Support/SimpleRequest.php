<?php

namespace Logger\Ntfy\Tests\Support;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class SimpleRequest implements RequestInterface {
	private StreamInterface $body;

	/** @var array<string, list<string>> */
	private array $headers = [];

	public function __construct(
		private string $method,
		string $uri,
		private string $protocolVersion = '1.1',
	) {
		$this->uri = new SimpleUri($uri);
		$this->body = new SimpleStream();
	}

	private UriInterface $uri;

	public function getRequestTarget(): string {
		$target = $this->uri->getPath();
		$query = $this->uri->getQuery();

		return $query === '' ? $target : "{$target}?{$query}";
	}

	public function withRequestTarget(string $requestTarget): RequestInterface {
		return $this;
	}

	public function getMethod(): string {
		return $this->method;
	}

	public function withMethod(string $method): RequestInterface {
		$clone = clone $this;
		$clone->method = $method;

		return $clone;
	}

	public function getUri(): UriInterface {
		return $this->uri;
	}

	public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface {
		$clone = clone $this;
		$clone->uri = $uri;

		return $clone;
	}

	public function getProtocolVersion(): string {
		return $this->protocolVersion;
	}

	public function withProtocolVersion(string $version): RequestInterface {
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

	public function withHeader(string $name, $value): RequestInterface {
		$clone = clone $this;
		$clone->headers[$name] = array_values(array_map('strval', is_array($value) ? $value : [$value]));

		return $clone;
	}

	public function withAddedHeader(string $name, $value): RequestInterface {
		$clone = clone $this;
		$clone->headers[$name] = [...($clone->headers[$name] ?? []), ...array_values(array_map('strval', is_array($value) ? $value : [$value]))];

		return $clone;
	}

	public function withoutHeader(string $name): RequestInterface {
		$clone = clone $this;
		unset($clone->headers[$name]);

		return $clone;
	}

	public function getBody(): StreamInterface {
		return $this->body;
	}

	public function withBody(StreamInterface $body): RequestInterface {
		$clone = clone $this;
		$clone->body = $body;

		return $clone;
	}
}
