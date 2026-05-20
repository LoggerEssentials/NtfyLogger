<?php

namespace Logger\Ntfy\Service\Url;

use Throwable;
use Uri\Rfc3986\Uri;

class PhpUriUrlBuilder implements UrlBuilder {
	public function resolve(string $url, string $baseUrl): ?string {
		try {
			$baseUri = Uri::parse($baseUrl);
			if($baseUri === null) {
				return null;
			}

			$resolvedUri = $baseUri->resolve($url);
			return $this->isAbsoluteHttpUri($resolvedUri) ? $resolvedUri->toRawString() : null;
		} catch(Throwable) {
			return null;
		}
	}

	public function isAbsoluteHttpUrl(string $url): bool {
		try {
			$uri = Uri::parse($url);
			return $uri !== null && $this->isAbsoluteHttpUri($uri);
		} catch(Throwable) {
			return false;
		}
	}

	private function isAbsoluteHttpUri(Uri $uri): bool {
		return in_array($uri->getScheme(), ['http', 'https'], true) && $uri->getHost() !== null;
	}
}
