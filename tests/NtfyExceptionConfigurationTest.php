<?php

namespace Logger\Ntfy\Tests;

use Logger\Ntfy\NtfyExceptionConfiguration;
use PHPUnit\Framework\TestCase;

class NtfyExceptionConfigurationTest extends TestCase {
	public function testShortensFilesBelowBasePathAndMarksConfiguredApplicationPaths(): void {
		$config = new NtfyExceptionConfiguration(
			basePath: '/var/www/app',
			applicationPaths: ['src', 'modules/Admin'],
		);

		self::assertSame('src/Service/Import.php', $config->formatFile('/var/www/app/src/Service/Import.php'));
		self::assertTrue($config->isApplicationFile('/var/www/app/src/Service/Import.php'));
		self::assertTrue($config->isApplicationFile('/var/www/app/modules/Admin/Controller.php'));

		self::assertSame('vendor/package/File.php', $config->formatFile('/var/www/app/vendor/package/File.php'));
		self::assertFalse($config->isApplicationFile('/var/www/app/vendor/package/File.php'));
		self::assertSame('/tmp/generated.php', $config->formatFile('/tmp/generated.php'));
	}

	public function testUsesBasePathAsApplicationFallbackWhenNoApplicationPathsAreConfigured(): void {
		$config = new NtfyExceptionConfiguration(basePath: '/var/www/app');

		self::assertTrue($config->isApplicationFile('/var/www/app/bootstrap.php'));
		self::assertFalse($config->isApplicationFile('/var/www-other/bootstrap.php'));
	}
}
