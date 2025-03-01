<?php

namespace Kir\Http\Routing\Common;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class ServerRequest implements ServerRequestInterface {
	private string $protocolVersion = '1.1';
	/** @var string[][] */
	private array $headers = [];
	private StreamInterface $body;
	/** @var array<string, mixed> */
	private array $serverParams = [];
	/** @var array<string, string> */
	private array $cookieParams = [];
	/** @var array<string, mixed> */
	private array $uploadedFiles = [];
	/** @var array<string, mixed> */
	private array $attributes = [];

	/**
	 * @param UriInterface $uri
	 * @param array<string, mixed> $queryParams
	 * @param mixed $parsedBody
	 */
	public function __construct(
		public string $method,
		public UriInterface $uri,
		public array $queryParams,
		public mixed $parsedBody
	) {}

	public function getProtocolVersion(): string {
		return $this->protocolVersion;
	}

	public function withProtocolVersion(string $version): MessageInterface {
		$clone = clone $this;
		$clone->protocolVersion = $version;
		return $clone;
	}

	public function getHeaders(): array {
		return $this->headers;
	}

	public function hasHeader(string $name): bool {
		return isset($this->headers[strtolower($name)]);
	}

	public function getHeader(string $name): array {
		return $this->headers[strtolower($name)] ?? [];
	}

	public function getHeaderLine(string $name): string {
		return implode(', ', $this->getHeader($name));
	}

	public function withHeader(string $name, $value): MessageInterface {
		$clone = clone $this;
		$clone->headers[strtolower($name)] = (array)$value;
		return $clone;
	}

	public function withAddedHeader(string $name, $value): MessageInterface {
		$clone = clone $this;
		$clone->headers[strtolower($name)] = array_merge($this->getHeader($name), (array)$value);
		return $clone;
	}

	public function withoutHeader(string $name): MessageInterface {
		$clone = clone $this;
		unset($clone->headers[strtolower($name)]);
		return $clone;
	}

	public function getBody(): StreamInterface {
		return $this->body;
	}

	public function withBody(StreamInterface $body): MessageInterface {
		$clone = clone $this;
		$clone->body = $body;
		return $clone;
	}

	public function getRequestTarget(): string {
		return (string) $this->uri;
	}

	public function withRequestTarget(string $requestTarget): RequestInterface {
		return $this;
	}

	public function getMethod(): string {
		return $this->method;
	}

	public function withMethod(string $method): RequestInterface {
		$clone = clone $this;
		$clone->method = $method;
		return $clone;
	}

	public function getUri(): UriInterface {
		return $this->uri;
	}

	public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface {
		$clone = clone $this;
		$clone->uri = $uri;
		return $clone;
	}

	/** @return array<string, mixed> */
	public function getServerParams(): array {
		return $this->serverParams;
	}

	/** @return array<string, string> */
	public function getCookieParams(): array {
		return $this->cookieParams;
	}

	/**
	 * @param array<string, string> $cookies
	 * @return ServerRequestInterface
	 */
	public function withCookieParams(array $cookies): ServerRequestInterface {
		$clone = clone $this;
		$clone->cookieParams = $cookies;
		return $clone;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getQueryParams(): array {
		return $this->queryParams;
	}

	/**
	 * @param array<string, mixed> $query
	 * @return ServerRequestInterface
	 */
	public function withQueryParams(array $query): ServerRequestInterface {
		$clone = clone $this;
		$clone->queryParams = $query;
		return $clone;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getUploadedFiles(): array {
		return $this->uploadedFiles;
	}

	/**
	 * @param array<string, mixed> $uploadedFiles
	 * @return ServerRequestInterface
	 */
	public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface {
		$clone = clone $this;
		$clone->uploadedFiles = $uploadedFiles;
		return $clone;
	}

	/**
	 * @return mixed
	 */
	public function getParsedBody(): mixed {
		return $this->parsedBody;
	}

	/**
	 * @param mixed $data
	 * @return ServerRequestInterface
	 */
	public function withParsedBody(mixed $data): ServerRequestInterface {
		$clone = clone $this;
		$clone->parsedBody = $data;
		return $clone;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getAttributes(): array {
		return $this->attributes;
	}

	public function getAttribute(string $name, $default = null): mixed {
		return $this->attributes[$name] ?? $default;
	}

	public function withAttribute(string $name, $value): ServerRequestInterface {
		$clone = clone $this;
		$clone->attributes[$name] = $value;
		return $clone;
	}

	public function withoutAttribute(string $name): ServerRequestInterface {
		$clone = clone $this;
		unset($clone->attributes[$name]);
		return $clone;
	}
}
