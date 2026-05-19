<?php

namespace Logger\Ntfy\Tests\Support;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

class SimpleRequestFactory implements RequestFactoryInterface {
	public function createRequest(string $method, $uri): RequestInterface {
		return new SimpleRequest($method, (string)$uri);
	}
}
