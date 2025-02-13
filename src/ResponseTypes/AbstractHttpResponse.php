<?php

namespace DvTeam\Routing\ResponseTypes;

use JsonSerializable;

abstract class AbstractHttpResponse implements JsonSerializable {
	public function __construct(private int $statusCode = 200) {}

	public function getStatusCode(): int {
		return $this->statusCode;
	}

	/**
	 * @param int $statusCode
	 */
	public function setStatusCode(int $statusCode): void {
		$this->statusCode = $statusCode;
	}
	
	/**
	 * @return array<string, mixed>
	 */
	public function jsonSerialize(): mixed {
		return [
			'type' => static::class,
			'status_code' => $this->statusCode
		];
	}
}
