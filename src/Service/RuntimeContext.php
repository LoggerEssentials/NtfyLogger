<?php

namespace Logger\Ntfy\Service;

class RuntimeContext {
	/**
	 * @param array<string, mixed> $context
	 * @param array<string, mixed> $server
	 */
	public static function describe(array $context, array $server): ?string {
		if(ContextUrlResolver::isWebContext($server)) {
			$url = ContextUrlResolver::resolve($context, $server);
			return $url === null ? null : "Web: `{$url}`";
		}

		$invocation = CliInvocationFormatter::format($server);
		return $invocation === null ? null : "CLI: `{$invocation}`";
	}
}
