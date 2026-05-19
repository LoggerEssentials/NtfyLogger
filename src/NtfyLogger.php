<?php

namespace Logger\Ntfy;

use Stringable;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class NtfyLogger extends AbstractLogger {
	public function __construct(
		private NtfyClient $client,
	) {}

	/**
	 * @param array<string, mixed> $context
	 * @param string|Stringable $message
	 */
	public function log($level, $message, array $context = []): void {
		$this->client->sendMessage($this->interpolate((string)$message, $context), $this->createParams($level, $context));
	}

	/**
	 * @param array<string, mixed> $context
	 */
	private function createParams(mixed $level, array $context): NtfyParams {
		$params = $context['ntfy'] ?? null;

		if($params instanceof NtfyParams) {
			return $params;
		}
		if(is_array($params)) {
			return NtfyParams::fromArray($params);
		}

		return NtfyParams::fromArray([
			'title' => $context['title'] ?? strtoupper((string)$level),
			'priority' => $context['priority'] ?? $this->getPriority((string)$level),
			'tags' => $context['tags'] ?? $this->getTags((string)$level),
			'click' => $context['click'] ?? null,
			'topic' => $context['topic'] ?? null,
			'sequence_id' => $context['sequence_id'] ?? null,
		]);
	}

	private function getPriority(string $level): int {
		return match($level) {
			LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL => 5,
			LogLevel::ERROR => 4,
			LogLevel::WARNING => 3,
			LogLevel::NOTICE, LogLevel::INFO => 2,
			default => 1,
		};
	}

	/**
	 * @return list<string>
	 */
	private function getTags(string $level): array {
		return match($level) {
			LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL => ['rotating_light'],
			LogLevel::ERROR => ['warning'],
			LogLevel::WARNING => ['triangular_flag_on_post'],
			LogLevel::NOTICE, LogLevel::INFO => ['information_source'],
			default => [],
		};
	}

	/**
	 * @param array<string, mixed> $context
	 */
	private function interpolate(string $message, array $context): string {
		$replace = [];

		foreach($context as $key => $value) {
			if($value === null || is_scalar($value) || $value instanceof Stringable) {
				$replace["{{$key}}"] = (string)$value;
			}
		}

		return strtr($message, $replace);
	}
}
