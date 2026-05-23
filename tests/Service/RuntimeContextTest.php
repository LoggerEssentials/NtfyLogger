<?php

namespace Logger\Ntfy\Tests\Service;

use Logger\Ntfy\Service\ContextUrlResolver;
use Logger\Ntfy\Service\RuntimeContext;
use PHPUnit\Framework\TestCase;

class RuntimeContextTest extends TestCase {
	public function testResolvesRelativeNtfyUrlAgainstCurrentWebHost(): void {
		$server = [
			'HTTPS' => 'on',
			'HTTP_HOST' => 'app.example.com',
			'REQUEST_URI' => '/orders/123?tab=events',
		];

		self::assertSame('https://app.example.com/admin/jobs?id=42', ContextUrlResolver::resolve([
			'ntfy_url' => '/admin/jobs?id=42',
		], $server));
	}

	public function testUsesCurrentRequestUrlWhenNoNtfyUrlWasProvided(): void {
		$server = [
			'HTTP_X_FORWARDED_PROTO' => 'https',
			'HTTP_HOST' => 'app.example.com',
			'REQUEST_URI' => '/orders/123?tab=events',
		];

		self::assertSame('https://app.example.com/orders/123?tab=events', ContextUrlResolver::resolve([], $server));
	}

	public function testFormatsCliInvocationWhenNoWebContextExists(): void {
		self::assertSame(
			"CLI: `bin/import --shop=main 'with space' (cwd: /var/www/app)`",
			RuntimeContext::describe([], [
				'argv' => ['bin/import', '--shop=main', 'with space'],
				'PWD' => '/var/www/app',
			]),
		);
	}
}
