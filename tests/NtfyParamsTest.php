<?php

namespace Logger\Ntfy\Tests;

use Logger\Ntfy\NtfyParams;
use PHPUnit\Framework\TestCase;

class NtfyParamsTest extends TestCase {
	public function testConvertsParametersToNtfyHeaders(): void {
		$params = new NtfyParams(
			title: 'Build failed',
			priority: 5,
			tags: ['rotating_light', 'computer'],
			markdown: true,
			click: 'https://example.com/builds/1',
			attach: 'https://example.com/report.txt',
			filename: 'report.txt',
			icon: 'https://example.com/icon.png',
			actions: [['action' => 'view', 'label' => 'Open', 'url' => 'https://example.com']],
			delay: '10m',
			email: 'ops@example.com',
			call: '+49123456789',
			sequenceId: 'build-1',
			cache: false,
			firebase: false,
			unifiedPush: true,
		);

		self::assertSame([
			'Title' => 'Build failed',
			'Priority' => '5',
			'Tags' => 'rotating_light,computer',
			'Markdown' => 'yes',
			'Click' => 'https://example.com/builds/1',
			'Attach' => 'https://example.com/report.txt',
			'Filename' => 'report.txt',
			'Icon' => 'https://example.com/icon.png',
			'Actions' => '[{"action":"view","label":"Open","url":"https://example.com"}]',
			'Delay' => '10m',
			'Email' => 'ops@example.com',
			'Call' => '+49123456789',
			'X-Sequence-ID' => 'build-1',
			'Cache' => 'no',
			'Firebase' => 'no',
			'UnifiedPush' => 'yes',
		], $params->toHeaders());
	}

	public function testCreatesParametersFromArrayAliasesAndNormalizesValues(): void {
		$params = NtfyParams::fromArray([
			'title' => 'Deploy',
			'priority' => true,
			'tags' => ' rocket, production ',
			'sequence_id' => 123,
			'cache' => true,
			'firebase' => true,
			'unified_push' => false,
		]);

		self::assertSame([
			'Title' => 'Deploy',
			'Priority' => '1',
			'Tags' => 'rocket,production',
			'X-Sequence-ID' => '123',
			'Cache' => 'yes',
			'Firebase' => 'yes',
			'UnifiedPush' => 'no',
		], $params->toHeaders());
	}
}
