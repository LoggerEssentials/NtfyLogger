<?php

namespace Logger\Ntfy\Tests;

use Logger\Ntfy\NtfyMessageBody;
use PHPUnit\Framework\TestCase;

class NtfyMessageBodyTest extends TestCase {
	public function testReportsRenderedByteLength(): void {
		$message = new NtfyMessageBody(message: 'Import failed');

		self::assertSame(strlen((string)$message), $message->getByteLength());
		self::assertFalse($message->isTruncated());
	}

	public function testTruncatesLongPartsAndCapsStacktraceBudget(): void {
		$message = new NtfyMessageBody(
			message: str_repeat('message ', 100),
			exception: str_repeat('exception ', 100),
			runtimeContext: str_repeat('context ', 100),
			stacktrace: str_repeat('stacktrace ', 100),
			extraContext: str_repeat('extra ', 100),
			maxBytes: 400,
		);

		$body = (string)$message;
		self::assertLessThanOrEqual(400, strlen($body));
		self::assertTrue($message->isTruncated());
		self::assertStringContainsString('... truncated ...', $body);

		$traceStart = strpos($body, "**Trace**\n");
		self::assertNotFalse($traceStart);
		$traceEnd = strpos($body, "\n\n", $traceStart);
		$traceBlock = $traceEnd === false ? substr($body, $traceStart) : substr($body, $traceStart, $traceEnd - $traceStart);
		self::assertLessThanOrEqual(100, strlen($traceBlock));
	}
}
