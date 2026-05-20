<?php

namespace Logger\Ntfy\Service\Url;

use Uri\Rfc3986\Uri;

class UrlBuilderFactory {
	public static function create(): UrlBuilder {
		if(PHP_VERSION_ID >= 80500 && class_exists(Uri::class)) {
			return new PhpUriUrlBuilder();
		}

		return new ManualUrlBuilder();
	}
}
