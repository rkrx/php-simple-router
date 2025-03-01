<?php

namespace Kir\Http\Routing\Common;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class Stream implements StreamInterface {
	/** @var resource|null */
	private $stream;
	/** @var array{mode: string, seekable?: bool} */
	private array $metadata;

	/**
	 * @param resource|null $stream
	 */
	public function __construct($stream = null) {
		if($stream === null) {
			$stream = fopen('php://memory', 'wb+');
			if($stream === false) {
				throw new RuntimeException('Unable to open stream');
			}
		}
		if(!is_resource($stream)) {
			throw new InvalidArgumentException('Stream must be a valid resource.');
		}
		$this->stream = $stream;
		$this->metadata = stream_get_meta_data($stream);
	}

	public function close(): void {
		if($this->stream) {
			fclose($this->stream);
			$this->stream = null;
		}
	}

	public function detach() {
		$stream = $this->stream;
		$this->stream = null;
		return $stream;
	}

	public function getSize(): ?int {
		if($this->stream === null) {
			throw new RuntimeException('Stream is closed');
		}
		$stats = fstat($this->stream);
		return $stats ? $stats['size'] : null;
	}

	public function tell(): int {
		if($this->stream === null) {
			throw new RuntimeException('Stream is closed');
		}
		$result = ftell($this->stream);
		if($result === false) {
			throw new RuntimeException('Can\'t move cursor in the stream');
		}
		return $result;
	}

	public function eof(): bool {
		if($this->stream === null) {
			throw new RuntimeException('Stream is closed');
		}
		return feof($this->stream);
	}

	public function isSeekable(): bool {
		return $this->metadata['seekable'] ?? false;
	}

	public function seek(int $offset, int $whence = SEEK_SET): void {
		if($this->stream === null) {
			throw new RuntimeException('Stream is closed');
		}
		if(!$this->isSeekable()) {
			throw new RuntimeException('Stream is not seekable');
		}
		fseek($this->stream, $offset, $whence);
	}

	public function rewind(): void {
		$this->seek(0);
	}

	public function isWritable(): bool {
		return str_contains($this->metadata['mode'], 'w') || str_contains($this->metadata['mode'], 'x') || str_contains($this->metadata['mode'], 'c') || str_contains($this->metadata['mode'], '+');
	}

	public function write(string $string): int {
		if($this->stream === null) {
			throw new RuntimeException('Stream is closed');
		}

		if(!$this->isWritable()) {
			throw new RuntimeException('Stream is not writable.');
		}

		$result = fwrite($this->stream, $string);

		if($result === false) {
			throw new RuntimeException('Can\'t write to stream');
		}

		return $result;
	}

	public function isReadable(): bool {
		return str_contains($this->metadata['mode'], 'r') || str_contains($this->metadata['mode'], '+');
	}

	public function read(int $length): string {
		if($this->stream === null) {
			throw new RuntimeException('Stream is closed');
		}

		if(!$this->isReadable()) {
			throw new RuntimeException('Stream is not readable.');
		}

		$result = fread($this->stream, max($length, 1));

		if($result === false) {
			throw new RuntimeException('Can\'t read stream');
		}

		return $result;
	}

	public function getContents(): string {
		if($this->stream === null) {
			throw new RuntimeException('Stream is closed');
		}

		if(!$this->isReadable()) {
			throw new RuntimeException('Stream is not readable.');
		}

		$contents = stream_get_contents($this->stream);

		if($contents === false) {
			throw new RuntimeException('Can\'t read stream');
		}

		return $contents;
	}

	public function getMetadata(?string $key = null): mixed {
		if($key === null) {
			return $this->metadata;
		}

		return $this->metadata[$key] ?? null;
	}

	public function __toString(): string {
		return $this->getContents();
	}
}
