<?php

namespace TablePress\Psr\Http\Client;

use TablePress\Psr\Http\Message\RequestInterface;
use TablePress\Psr\Http\Message\ResponseInterface;

interface ClientInterface
{
	/**
	 * Sends a PSR-7 request and returns a PSR-7 response.
	 *
	 * @param RequestInterface $request
	 *
	 * @return ResponseInterface
	 *
	 * @throws \TablePress\Psr\Http\Client\ClientExceptionInterface If an error happens while processing the request.
	 */
	public function sendRequest(RequestInterface $request): ResponseInterface;
}
