<?php

namespace DvTeam\Routing\ResponseTypes;

use Stringable;

class MimeTypeContentResponse extends AbstractHttpResponse implements Stringable {
	/**
	 * @param string $content
	 * @param string $contentMimeType
	 * @param int $statusCode
	 */
	public function __construct(private readonly string $content, private readonly string $contentMimeType, int $statusCode = 200) {
		parent::__construct($statusCode);
	}

	/**
	 * @return string
	 */
	public function getContent(): string {
		return $this->content;
	}

	/**
	 * @return string
	 */
	public function getContentMimeType(): string {
		return $this->contentMimeType;
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		return $this->content;
	}
}
