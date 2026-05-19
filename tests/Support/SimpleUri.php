<?php

namespace Logger\Ntfy\Tests\Support;

use Psr\Http\Message\UriInterface;

class SimpleUri implements UriInterface {
	public function __construct(
		private string $uri,
	) {}

	public function __toString(): string {
		return $this->uri;
	}

	public function getScheme(): string {
		return (string)(parse_url($this->uri, PHP_URL_SCHEME) ?? '');
	}

	public function getAuthority(): string {
		return $this->getHost();
	}

	public function getUserInfo(): string {
		return '';
	}

	public function getHost(): string {
		return (string)(parse_url($this->uri, PHP_URL_HOST) ?? '');
	}

	public function getPort(): ?int {
		return parse_url($this->uri, PHP_URL_PORT);
	}

	public function getPath(): string {
		return (string)(parse_url($this->uri, PHP_URL_PATH) ?? '');
	}

	public function getQuery(): string {
		return (string)(parse_url($this->uri, PHP_URL_QUERY) ?? '');
	}

	public function getFragment(): string {
		return (string)(parse_url($this->uri, PHP_URL_FRAGMENT) ?? '');
	}

	public function withScheme(string $scheme): UriInterface {
		return $this;
	}

	public function withUserInfo(string $user, ?string $password = null): UriInterface {
		return $this;
	}

	public function withHost(string $host): UriInterface {
		return $this;
	}

	public function withPort(?int $port): UriInterface {
		return $this;
	}

	public function withPath(string $path): UriInterface {
		return $this;
	}

	public function withQuery(string $query): UriInterface {
		return $this;
	}

	public function withFragment(string $fragment): UriInterface {
		return $this;
	}
}
