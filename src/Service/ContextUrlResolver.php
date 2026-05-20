<?php

namespace Logger\Ntfy\Service;

use Logger\Ntfy\Service\Url\UrlBuilder;
use Logger\Ntfy\Service\Url\UrlBuilderFactory;
use Stringable;

class ContextUrlResolver {
	/**
	 * @param array<string, mixed> $context
	 * @param array<string, mixed> $server
	 */
	public static function resolve(array $context, array $server, ?UrlBuilder $urlBuilder = null): ?string {
		$urlBuilder ??= UrlBuilderFactory::create();
		$baseUrl = self::getBaseUrl($server);

		$contextUrl = self::normalizeString($context['ntfy_url'] ?? null);
		if($contextUrl !== null) {
			return $baseUrl !== null ? $urlBuilder->resolve($contextUrl, $baseUrl) : ($urlBuilder->isAbsoluteHttpUrl($contextUrl) ? $contextUrl : null);
		}

		if($baseUrl === null) {
			return null;
		}

		return $urlBuilder->resolve(self::normalizeRequestTarget($server['REQUEST_URI'] ?? '/'), $baseUrl);
	}

	/**
	 * @param array<string, mixed> $server
	 */
	public static function isWebContext(array $server): bool {
		return self::getHost($server) !== null && isset($server['REQUEST_URI']);
	}

	/**
	 * @param array<string, mixed> $server
	 */
	private static function getBaseUrl(array $server): ?string {
		$host = self::getHost($server);
		if($host === null) {
			return null;
		}

		return self::getScheme($server).'://'.$host.'/';
	}

	/**
	 * @param array<string, mixed> $server
	 */
	private static function getHost(array $server): ?string {
		$host = self::normalizeString($server['HTTP_HOST'] ?? null) ?? self::normalizeString($server['SERVER_NAME'] ?? null);
		if($host === null) {
			return null;
		}

		$port = self::normalizeString($server['SERVER_PORT'] ?? null);
		if($port !== null && !str_contains($host, ':') && !in_array($port, ['80', '443'], true)) {
			$host .= ":{$port}";
		}

		return $host;
	}

	/**
	 * @param array<string, mixed> $server
	 */
	private static function getScheme(array $server): string {
		$https = self::normalizeString($server['HTTPS'] ?? null);
		if($https !== null && $https !== '' && strtolower($https) !== 'off') {
			return 'https';
		}

		$forwardedProto = self::normalizeString($server['HTTP_X_FORWARDED_PROTO'] ?? null);
		if($forwardedProto !== null) {
			$scheme = strtolower(trim(explode(',', $forwardedProto)[0]));
			if(in_array($scheme, ['http', 'https'], true)) {
				return $scheme;
			}
		}

		return 'http';
	}

	private static function normalizeString(mixed $value): ?string {
		if($value === null) {
			return null;
		}
		if(is_scalar($value) || $value instanceof Stringable) {
			$value = trim((string)$value);
			return $value === '' ? null : $value;
		}

		return null;
	}

	private static function normalizeRequestTarget(mixed $value): string {
		$requestTarget = self::normalizeString($value);
		if($requestTarget === null) {
			return '/';
		}

		return str_starts_with($requestTarget, '/') ? $requestTarget : "/{$requestTarget}";
	}
}
