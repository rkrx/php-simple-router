<?php

namespace DvTeam\Routing\ResponseTypes;

use Stringable;

class BinaryContentResponse extends AbstractHttpResponse implements Stringable {
	public function __construct(
		private readonly string $content,
		private readonly string $mimeType = 'application/octet-stream',
		int $statusCode = 200
	) {
		parent::__construct($statusCode);
	}

	public function getMimeType(): string {
		return $this->mimeType;
	}

	public function getContentAsString(): string {
		return $this->content;
	}

	public function __toString(): string {
		return $this->content;
	}
}
