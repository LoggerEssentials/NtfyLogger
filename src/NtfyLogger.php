<?php

namespace Logger\Ntfy;

use Logger\Ntfy\Service\ContextUrlResolver;
use Logger\Ntfy\Service\RuntimeContext;
use Stringable;
use Throwable;

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
		$this->client->sendMessage($this->createMessage((string)$message, $context), $this->createParams($level, $context));
	}

	/**
	 * @param array<string, mixed> $context
	 */
	private function createParams(mixed $level, array $context): NtfyParams {
		$params = $context['ntfy'] ?? null;
		$level = is_scalar($level) || $level instanceof Stringable ? (string)$level : '';
		$click = $context['click'] ?? ContextUrlResolver::resolve($context, $this->getServer()) ?? $context['ntfy_url'] ?? $context['url'] ?? null;

		if($params instanceof NtfyParams) {
			return $this->withExceptionMarkdown($params, $context);
		}
		if(is_array($params)) {
			return $this->withExceptionMarkdown(NtfyParams::fromArray($params), $context);
		}

		return $this->withExceptionMarkdown(NtfyParams::fromArray([
			'title' => $context['title'] ?? strtoupper($level),
			'priority' => $context['priority'] ?? $this->getPriority($level),
			'tags' => $context['tags'] ?? $this->getTags($level),
			'click' => $click,
			'topic' => $context['topic'] ?? null,
			'sequence_id' => $context['sequence_id'] ?? null,
		]), $context);
	}

	/**
	 * @param array<string, mixed> $context
	 */
	private function createMessage(string $message, array $context): string {
		$message = $this->interpolate($message, $context);
		$exception = $context['exception'] ?? null;

		if(!$exception instanceof Throwable) {
			return $message;
		}

		$blocks = [$message];
		$runtimeContext = RuntimeContext::describe($context, $this->getServer());
		if($runtimeContext !== null) {
			$blocks[] = "**Context**\n{$runtimeContext}";
		}
		$blocks[] = (new NtfyExceptionMarkdownRenderer($this->client->getConfiguration()->getExceptionConfiguration()))->render($exception);

		return implode("\n\n", $blocks);
	}

	/**
	 * @param array<string, mixed> $context
	 */
	private function withExceptionMarkdown(NtfyParams $params, array $context): NtfyParams {
		if(!($context['exception'] ?? null) instanceof Throwable) {
			return $params;
		}

		$params = clone $params;
		$params->markdown = true;

		return $params;
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
	 * @return array<string, mixed>
	 */
	private function getServer(): array {
		$server = [];
		foreach($_SERVER as $key => $value) {
			if(is_string($key)) {
				$server[$key] = $value;
			}
		}

		return $server;
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
