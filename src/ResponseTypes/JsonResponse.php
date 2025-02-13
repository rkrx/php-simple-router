<?php

namespace DvTeam\Routing\ResponseTypes;

use Override;

class JsonResponse extends AbstractHttpResponse {
	public function __construct(private readonly mixed $data, int $statusCode = 200) {
		parent::__construct($statusCode);
	}

	public function getData(): mixed {
		return $this->data;
	}

	#[Override]
    public function jsonSerialize(): mixed {
		$data = parent::jsonSerialize();
		$data['data'] = $this->data;
		return $data;
	}
}
