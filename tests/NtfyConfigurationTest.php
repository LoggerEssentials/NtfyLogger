<?php

namespace Logger\Ntfy\Tests;

use InvalidArgumentException;
use Logger\Ntfy\NtfyConfiguration;
use PHPUnit\Framework\TestCase;

class NtfyConfigurationTest extends TestCase {
	public function testNormalizesTopicAndServerUrlForPublishUrl(): void {
		$config = new NtfyConfiguration(topic: ' /ops-alerts/ ', serverUrl: 'https://ntfy.example.com/');

		self::assertSame('ops-alerts', $config->getTopic());
		self::assertSame('https://ntfy.example.com', $config->getServerUrl());
		self::assertSame('https://ntfy.example.com/ops-alerts', $config->getPublishUrl());
		self::assertSame('https://ntfy.example.com/deployments/prod%201', $config->getPublishUrl('deployments', 'prod 1'));
	}

	public function testSupportsBearerAndBasicAuthenticationHeaders(): void {
		self::assertSame(
			['Authorization' => 'Bearer tk_secret'],
			(new NtfyConfiguration(topic: 'alerts', token: 'tk_secret'))->getAuthenticationHeaders(),
		);

		self::assertSame(
			['Authorization' => 'Basic '.base64_encode('logger:secret')],
			(new NtfyConfiguration(topic: 'alerts', username: 'logger', password: 'secret'))->getAuthenticationHeaders(),
		);
	}

	public function testRejectsInvalidConfiguration(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Use either token auth or username/password auth, not both.');

		new NtfyConfiguration(topic: 'alerts', token: 'token', username: 'logger', password: 'secret');
	}

	public function testRejectsPartialBasicAuthentication(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Username and password must be configured together.');

		new NtfyConfiguration(topic: 'alerts', username: 'logger');
	}
}
