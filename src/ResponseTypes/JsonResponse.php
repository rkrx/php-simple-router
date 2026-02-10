<?php

namespace Kir\Http\Routing\ResponseTypes;

use Override;

class JsonResponse extends AbstractHttpResponse {
	/**
	 * @param mixed $data
	 * @param int $statusCode
	 * @param array<string, mixed>|array{prettyPrint?: bool} $options
	 */
	public function __construct(private readonly mixed $data, int $statusCode = 200, public array $options = []) {
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
