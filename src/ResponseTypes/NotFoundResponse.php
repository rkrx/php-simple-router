<?php

namespace DvTeam\Routing\ResponseTypes;

class NotFoundResponse extends AbstractHttpResponse {
	public function __construct(
		private readonly string $localizedMessage = '',
		int $statusCode = 404
	) {
		parent::__construct($statusCode);
	}

	/**
	 * @return string
	 */
	public function getLocalizedMessage(): string {
		return $this->localizedMessage;
	}
}
