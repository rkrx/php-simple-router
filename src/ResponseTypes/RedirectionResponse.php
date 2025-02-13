<?php

namespace DvTeam\Routing\ResponseTypes;

use Override;

class RedirectionResponse extends AbstractHttpResponse {
	public function __construct(
		private readonly string $url,
		int $statusCode = 302
	) {
		parent::__construct($statusCode);
	}

	public function getUrl(): string {
		return $this->url;
	}

	#[Override]
    public function jsonSerialize(): mixed {
		$data = parent::jsonSerialize();
		$data['url'] = $this->url;
		return $data;
	}
}
