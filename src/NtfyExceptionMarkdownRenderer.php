<?php

namespace Logger\Ntfy;

use Throwable;

class NtfyExceptionMarkdownRenderer {
	public function __construct(
		private NtfyExceptionConfiguration $configuration = new NtfyExceptionConfiguration(),
	) {}

	public function render(Throwable $exception): string {
		$exceptions = [];

		do {
			$exceptions[] = $exception;
			$exception = $exception->getPrevious();
		} while($exception !== null);

		$blocks = [];
		foreach(array_reverse($exceptions) as $index => $exception) {
			$blocks[] = $this->renderException($exception, $index !== 0);
		}

		return implode("\n\n", $blocks);
	}

	private function renderException(Throwable $exception, bool $following): string {
		$lines = [
			($following ? '### Following exception: ' : '### Exception: ').$this->code($exception::class),
		];

		if($exception->getMessage() !== '') {
			$lines[] = $this->code($exception->getMessage());
		}

		$lines[] = '';
		$lines[] = '**Thrown at**';
		$lines[] = $this->formatLocation($exception->getFile(), $exception->getLine());

		$frames = $this->renderFrames($exception->getTrace());
		if($frames !== []) {
			$lines[] = '';
			$lines[] = '**Trace**';
			array_push($lines, ...$frames);
		}

		return implode("\n", $lines);
	}

	/**
	 * @param list<array<string, mixed>> $trace
	 * @return list<string>
	 */
	private function renderFrames(array $trace): array {
		$lines = [];

		foreach($trace as $index => $frame) {
			$number = $index + 1;
			$line = $frame['line'] ?? null;
			$location = isset($frame['file']) && is_string($frame['file'])
				? $this->formatLocation($frame['file'], is_int($line) ? $line : null)
				: $this->code('[internal]');
			$call = $this->formatCall($frame);

			$lines[] = "{$number}. {$location}";
			if($call !== null) {
				$lines[] = '   '.$call;
			}
		}

		return $lines;
	}

	private function formatLocation(string $file, ?int $line): string {
		$location = $this->configuration->formatFile($file).($line !== null ? ":{$line}" : '');
		$location = $this->code($location);

		return $this->configuration->isApplicationFile($file) ? "**{$location}**" : $location;
	}

	/**
	 * @param array<string, mixed> $frame
	 */
	private function formatCall(array $frame): ?string {
		$function = isset($frame['function']) && is_string($frame['function']) ? $frame['function'] : null;
		if($function === null || $function === '') {
			return null;
		}

		$class = isset($frame['class']) && is_string($frame['class']) ? $frame['class'] : '';
		$type = isset($frame['type']) && is_string($frame['type']) ? $frame['type'] : '';

		return $this->code("{$class}{$type}{$function}()");
	}

	private function code(string $value): string {
		return '`'.str_replace('`', "'", $value).'`';
	}
}
