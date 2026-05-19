<?php

namespace Logger\Ntfy;

use InvalidArgumentException;
use SensitiveParameter;

class NtfyConfiguration {
	private string $serverUrl;
	private string $topic;

	public function __construct(
		string $topic,
		#[SensitiveParameter]
		private ?string $token = null,
		string $serverUrl = 'https://ntfy.sh',
		#[SensitiveParameter]
		private ?string $username = null,
		#[SensitiveParameter]
		private ?string $password = null,
		private ?NtfyExceptionConfiguration $exceptionConfiguration = null,
	) {
		$this->topic = $this->normalizeTopic($topic);
		$this->serverUrl = $this->normalizeServerUrl($serverUrl);

		if($this->token !== null && ($this->username !== null || $this->password !== null)) {
			throw new InvalidArgumentException('Use either token auth or username/password auth, not both.');
		}
		if(($this->username === null) !== ($this->password === null)) {
			throw new InvalidArgumentException('Username and password must be configured together.');
		}
	}

	public function getServerUrl(): string {
		return $this->serverUrl;
	}

	public function getTopic(): string {
		return $this->topic;
	}

	public function getExceptionConfiguration(): NtfyExceptionConfiguration {
		return $this->exceptionConfiguration ??= new NtfyExceptionConfiguration();
	}

	public function getPublishUrl(?string $topic = null, ?string $sequenceId = null): string {
		$path = rawurlencode($this->normalizeTopic($topic ?? $this->topic));

		if($sequenceId !== null && $sequenceId !== '') {
			$path .= '/'.rawurlencode($sequenceId);
		}

		return "{$this->serverUrl}/{$path}";
	}

	/**
	 * @return array<string, string>
	 */
	public function getAuthenticationHeaders(): array {
		if($this->token !== null && $this->token !== '') {
			return ['Authorization' => "Bearer {$this->token}"];
		}
		if($this->username !== null && $this->password !== null) {
			$credentials = base64_encode("{$this->username}:{$this->password}");
			return ['Authorization' => "Basic {$credentials}"];
		}

		return [];
	}

	private function normalizeServerUrl(string $serverUrl): string {
		$serverUrl = rtrim(trim($serverUrl), '/');

		if($serverUrl === '') {
			throw new InvalidArgumentException('Server URL must not be empty.');
		}

		return $serverUrl;
	}

	private function normalizeTopic(string $topic): string {
		$topic = trim($topic, '/ ');

		if($topic === '') {
			throw new InvalidArgumentException('Topic must not be empty.');
		}

		return $topic;
	}
}
