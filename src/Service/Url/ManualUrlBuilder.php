<?php

namespace Logger\Ntfy\Service\Url;

class ManualUrlBuilder implements UrlBuilder {
	public function resolve(string $url, string $baseUrl): ?string {
		$url = trim($url);
		if($url === '') {
			return null;
		}
		if($this->isAbsoluteHttpUrl($url)) {
			return $url;
		}

		$base = parse_url($baseUrl);
		if(!is_array($base) || !isset($base['scheme'], $base['host']) || !in_array($base['scheme'], ['http', 'https'], true)) {
			return null;
		}

		if(str_starts_with($url, '//')) {
			return "{$base['scheme']}:{$url}";
		}

		$authority = $base['host'];
		if(isset($base['port'])) {
			$authority .= ":{$base['port']}";
		}

		return "{$base['scheme']}://{$authority}{$this->resolvePath($url, $base['path'] ?? '/')}";
	}

	public function isAbsoluteHttpUrl(string $url): bool {
		$parts = parse_url(trim($url));

		return is_array($parts)
			&& isset($parts['scheme'], $parts['host'])
			&& in_array(strtolower($parts['scheme']), ['http', 'https'], true);
	}

	private function resolvePath(string $url, string $basePath): string {
		if(str_starts_with($url, '?') || str_starts_with($url, '#')) {
			return $this->baseDirectory($basePath).$url;
		}
		if(str_starts_with($url, '/')) {
			return $url;
		}

		return $this->baseDirectory($basePath).$url;
	}

	private function baseDirectory(string $basePath): string {
		if($basePath === '' || str_ends_with($basePath, '/')) {
			return $basePath === '' ? '/' : $basePath;
		}

		$directory = dirname($basePath);
		return ($directory === '.' ? '' : $directory).'/';
	}
}
