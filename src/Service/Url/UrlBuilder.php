<?php

namespace Logger\Ntfy\Service\Url;

interface UrlBuilder {
	public function resolve(string $url, string $baseUrl): ?string;

	public function isAbsoluteHttpUrl(string $url): bool;
}
