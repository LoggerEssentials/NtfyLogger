<?php

namespace Logger\Ntfy\Service;

use Stringable;

class CliInvocationFormatter {
	/**
	 * @param array<string, mixed> $server
	 */
	public static function format(array $server): ?string {
		$argv = $server['argv'] ?? null;
		if(!is_array($argv) || $argv === []) {
			return null;
		}

		$parts = [];
		foreach($argv as $argument) {
			if(is_scalar($argument) || $argument instanceof Stringable) {
				$parts[] = self::quote((string)$argument);
			}
		}
		if($parts === []) {
			return null;
		}

		$command = implode(' ', $parts);
		$cwd = self::normalizeString($server['PWD'] ?? getcwd() ?: null);

		return $cwd === null ? $command : "{$command} (cwd: {$cwd})";
	}

	private static function quote(string $value): string {
		if($value === '') {
			return "''";
		}
		if(preg_match('/^[A-Za-z0-9_\/.=:,@+-]+$/', $value) === 1) {
			return $value;
		}

		return "'".str_replace("'", "'\\''", $value)."'";
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
}
