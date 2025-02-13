<?php

namespace DvTeam\Routing\ResponseTypes;

use Stringable;

class HtmlResponse extends AbstractHttpResponse implements Stringable {
	public function __construct(private readonly string $content, int $statusCode = 200) {
		parent::__construct($statusCode);
	}

	public function __toString(): string {
		return $this->content;
	}
}
