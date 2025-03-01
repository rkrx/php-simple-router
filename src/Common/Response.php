<?php

namespace Kir\Http\Routing\Common;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Response implements ResponseInterface {
	/** @var string[][] */
	private array $headers = [];
	private string $reasonPhrase = 'OK';

	/**
	 * @param StreamInterface $body
	 * @param int $statusCode
	 * @param string[][] $headers
	 * @param string $protocolVersion
	 */
	public function __construct(
		private StreamInterface $body,
		private int $statusCode = 200,
		array $headers = [],
		private string $protocolVersion = '1.1'
	) {
		$this->headers = $this->normalizeHeaders($headers);
	}

	public function getProtocolVersion(): string {
		return $this->protocolVersion;
	}

	public function withProtocolVersion(string $version): static {
		$new = clone $this;
		$new->protocolVersion = $version;
		return $new;
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

	public function withHeader(string $name, $value): static {
		$new = clone $this;
		$new->headers[strtolower($name)] = (array) $value;
		return $new;
	}

	public function withAddedHeader(string $name, $value): static {
		$new = clone $this;
		$lowerName = strtolower($name);
		if (!isset($new->headers[$lowerName])) {
			$new->headers[$lowerName] = [];
		}
		$new->headers[$lowerName] = array_merge($new->headers[$lowerName], (array) $value);
		return $new;
	}

	public function withoutHeader(string $name): static {
		$new = clone $this;
		unset($new->headers[strtolower($name)]);
		return $new;
	}

	public function getBody(): StreamInterface {
		return $this->body;
	}

	public function withBody(StreamInterface $body): static {
		$new = clone $this;
		$new->body = $body;
		return $new;
	}

	public function getStatusCode(): int {
		return $this->statusCode;
	}

	public function withStatus(int $code, string $reasonPhrase = ''): static {
		$new = clone $this;
		$new->statusCode = $code;
		$new->reasonPhrase = $reasonPhrase ?: $this->getDefaultReasonPhrase($code);
		return $new;
	}

	public function getReasonPhrase(): string {
		return $this->reasonPhrase;
	}

	/**
	 * @param string[][] $headers
	 * @return string[][]
	 */
	private function normalizeHeaders(array $headers): array {
		$normalized = [];
		foreach ($headers as $name => $value) {
			$normalized[strtolower($name)] = (array) $value;
		}
		return $normalized;
	}

	private function getDefaultReasonPhrase(int $code): string {
		return match ($code) {
			200 => 'OK',
			201 => 'Created',
			204 => 'No Content',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			403 => 'Forbidden',
			404 => 'Not Found',
			500 => 'Internal Server Error',
			default => '',
		};
	}
}
