<?php

namespace Logger\Ntfy\Tests\Support;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class CapturingHttpClient implements ClientInterface {
	public ?RequestInterface $request = null;

	public function __construct(
		private ResponseInterface $response = new SimpleResponse(),
	) {}

	public function sendRequest(RequestInterface $request): ResponseInterface {
		$this->request = $request;

		return $this->response;
	}
}
