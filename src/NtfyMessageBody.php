<?php

namespace Logger\Ntfy;

use Stringable;

class NtfyMessageBody implements Stringable {
	private const MAX_BYTES = 4096;
	private const TRUNCATED_SUFFIX = "\n... truncated ...";

	/** @var array<string, float> */
	private const BUDGET_WEIGHTS = [
		'message' => 0.28,
		'exception' => 0.27,
		'runtime_context' => 0.15,
		'stacktrace' => 0.25,
		'extra_context' => 0.05,
	];

	private ?string $rendered = null;
	private ?bool $truncated = null;

	public function __construct(
		public string $message,
		public ?string $exception = null,
		public ?string $runtimeContext = null,
		public ?string $stacktrace = null,
		public ?string $extraContext = null,
		private int $maxBytes = self::MAX_BYTES,
	) {}

	public function __toString(): string {
		return $this->render();
	}

	public function getByteLength(): int {
		return strlen($this->render());
	}

	public function isTruncated(): bool {
		$this->render();

		return $this->truncated === true;
	}

	private function render(): string {
		if($this->rendered !== null) {
			return $this->rendered;
		}

		$parts = $this->getParts();
		$full = $this->renderParts($parts);
		if(strlen($full) <= $this->maxBytes) {
			$this->truncated = false;
			return $this->rendered = $full;
		}

		$this->truncated = true;
		return $this->rendered = $this->renderParts($this->truncateParts($parts));
	}

	/**
	 * @return array<string, string>
	 */
	private function getParts(): array {
		$parts = ['message' => $this->message];
		if($this->exception !== null && $this->exception !== '') {
			$parts['exception'] = $this->exception;
		}
		if($this->runtimeContext !== null && $this->runtimeContext !== '') {
			$parts['runtime_context'] = "**Context**\n{$this->runtimeContext}";
		}
		if($this->stacktrace !== null && $this->stacktrace !== '') {
			$parts['stacktrace'] = "**Trace**\n{$this->stacktrace}";
		}
		if($this->extraContext !== null && $this->extraContext !== '') {
			$parts['extra_context'] = "**Details**\n{$this->extraContext}";
		}

		return $parts;
	}

	/**
	 * @param array<string, string> $parts
	 */
	private function renderParts(array $parts): string {
		return implode("\n\n", array_values($parts));
	}

	/**
	 * @param array<string, string> $parts
	 * @return array<string, string>
	 */
	private function truncateParts(array $parts): array {
		$separatorBytes = max(0, count($parts) - 1) * strlen("\n\n");
		$contentBudget = max(0, $this->maxBytes - $separatorBytes);
		$limits = $this->calculatePartLimits($parts, $contentBudget);
		$truncated = [];

		foreach($parts as $key => $value) {
			$truncated[$key] = $this->truncateToBytes($value, $limits[$key] ?? 0);
		}

		while(strlen($this->renderParts($truncated)) > $this->maxBytes) {
			$key = $this->findLargestPartKey($truncated);
			if($key === null || strlen($truncated[$key]) === 0) {
				break;
			}
			$truncated[$key] = $this->truncateToBytes($truncated[$key], strlen($truncated[$key]) - 1);
		}

		return $truncated;
	}

	/**
	 * @param array<string, string> $parts
	 * @return array<string, int>
	 */
	private function calculatePartLimits(array $parts, int $contentBudget): array {
		$limits = [];
		$remainingBudget = $contentBudget;
		$oversizedKeys = [];

		foreach($parts as $key => $value) {
			$limit = (int)floor($contentBudget * (self::BUDGET_WEIGHTS[$key] ?? 0.05));
			if($key === 'stacktrace') {
				$limit = min($limit, (int)floor($this->maxBytes * 0.25));
			}
			$limit = max(0, $limit);
			$limits[$key] = min(strlen($value), $limit);
			$remainingBudget -= $limits[$key];

			if(strlen($value) > $limit) {
				$oversizedKeys[] = $key;
			}
		}

		while($remainingBudget > 0 && $oversizedKeys !== []) {
			$changed = false;
			foreach($oversizedKeys as $index => $key) {
				if($remainingBudget <= 0) {
					break;
				}
				$maxForKey = $key === 'stacktrace' ? (int)floor($this->maxBytes * 0.25) : strlen($parts[$key]);
				if($limits[$key] >= strlen($parts[$key]) || $limits[$key] >= $maxForKey) {
					unset($oversizedKeys[$index]);
					continue;
				}

				$limits[$key]++;
				$remainingBudget--;
				$changed = true;
			}
			$oversizedKeys = array_values($oversizedKeys);
			if(!$changed) {
				break;
			}
		}

		return $limits;
	}

	/**
	 * @param array<string, string> $parts
	 */
	private function findLargestPartKey(array $parts): ?string {
		$largestKey = null;
		$largestLength = 0;
		foreach($parts as $key => $value) {
			$length = strlen($value);
			if($length > $largestLength) {
				$largestKey = $key;
				$largestLength = $length;
			}
		}

		return $largestKey;
	}

	private function truncateToBytes(string $value, int $maxBytes): string {
		if(strlen($value) <= $maxBytes) {
			return $value;
		}
		if($maxBytes <= 0) {
			return '';
		}

		$suffix = self::TRUNCATED_SUFFIX;
		if($maxBytes <= strlen($suffix)) {
			return substr($suffix, -$maxBytes);
		}

		return $this->validUtf8Prefix($value, $maxBytes - strlen($suffix)).$suffix;
	}

	private function validUtf8Prefix(string $value, int $maxBytes): string {
		$value = substr($value, 0, $maxBytes);
		while($value !== '' && preg_match('//u', $value) !== 1) {
			$value = substr($value, 0, -1);
		}

		return $value;
	}
}
