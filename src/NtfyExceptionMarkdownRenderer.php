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
		foreach(array_reverse($exceptions) as $index => $ex) {
			$blocks[] = $this->renderException($ex, $index !== 0);
		}

		return implode("\n\n", $blocks);
	}

	public function renderInnerMost(Throwable $exception, int $maxFrames = 10): string {
		while($exception->getPrevious() !== null) {
			$exception = $exception->getPrevious();
		}

		return $this->renderException($exception, false, $maxFrames);
	}

	public function renderInnerMostSummary(Throwable $exception): string {
		while($exception->getPrevious() !== null) {
			$exception = $exception->getPrevious();
		}

		return $this->renderException($exception, false, null, false);
	}

	public function renderInnerMostTrace(Throwable $exception, int $maxFrames = 10): string {
		while($exception->getPrevious() !== null) {
			$exception = $exception->getPrevious();
		}

		return implode("\n", $this->renderFrames($exception->getTrace(), $maxFrames));
	}

	private function renderException(Throwable $exception, bool $following, ?int $maxFrames = null, bool $includeTrace = true): string {
		$lines = [
			($following ? '### Following exception: ' : '### Exception: ').$this->code($exception::class),
		];

		if($exception->getMessage() !== '') {
			$lines[] = $this->code($exception->getMessage());
		}

		$lines[] = '';
		$lines[] = '**Thrown at**';
		$lines[] = $this->formatLocation($exception->getFile(), $exception->getLine());

		$frames = $includeTrace ? $this->renderFrames($exception->getTrace(), $maxFrames) : [];
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
	private function renderFrames(array $trace, ?int $maxFrames = null): array {
		$lines = [];
		$omittedFrames = 0;
		if($maxFrames !== null && count($trace) > $maxFrames) {
			$omittedFrames = count($trace) - $maxFrames;
			$trace = array_slice($trace, -$maxFrames);
		}

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
		if($omittedFrames > 0) {
			$lines[] = sprintf('... %d more frame%s omitted ...', $omittedFrames, $omittedFrames === 1 ? '' : 's');
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
		return sprintf('`%s`', strtr($value, ['`' => "'"]));
	}
}
