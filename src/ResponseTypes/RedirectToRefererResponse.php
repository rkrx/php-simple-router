<?php

namespace DvTeam\Routing\ResponseTypes;

class RedirectToRefererResponse extends AbstractHttpResponse {
	/**
	 * @param int $statusCode
	 */
	public function __construct(int $statusCode = 302) {
		parent::__construct($statusCode);
	}
}
