<?php

namespace Logger\Ntfy\Tests\Support;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

class SimpleStream implements StreamInterface {
	private int $position = 0;

	public function __construct(
		private string $content = '',
	) {}

	public function __toString(): string {
		return $this->content;
	}

	public function close(): void {}

	public function detach() {
		return null;
	}

	public function getSize(): ?int {
		return strlen($this->content);
	}

	public function tell(): int {
		return $this->position;
	}

	public function eof(): bool {
		return $this->position >= strlen($this->content);
	}

	public function isSeekable(): bool {
		return true;
	}

	public function seek(int $offset, int $whence = SEEK_SET): void {
		$position = match($whence) {
			SEEK_SET => $offset,
			SEEK_CUR => $this->position + $offset,
			SEEK_END => strlen($this->content) + $offset,
			default => throw new RuntimeException('Invalid seek mode.'),
		};

		if($position < 0) {
			throw new RuntimeException('Invalid stream position.');
		}

		$this->position = $position;
	}

	public function rewind(): void {
		$this->position = 0;
	}

	public function isWritable(): bool {
		return true;
	}

	public function write(string $string): int {
		$this->content .= $string;
		$this->position = strlen($this->content);

		return strlen($string);
	}

	public function isReadable(): bool {
		return true;
	}

	public function read(int $length): string {
		$result = substr($this->content, $this->position, $length);
		$this->position += strlen($result);

		return $result;
	}

	public function getContents(): string {
		return substr($this->content, $this->position);
	}

	public function getMetadata(?string $key = null) {
		return $key === null ? [] : null;
	}
}
