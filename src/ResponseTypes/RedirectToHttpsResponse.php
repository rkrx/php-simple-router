<?php

namespace DvTeam\Routing\ResponseTypes;

class RedirectToHttpsResponse extends AbstractHttpResponse {
	/**
	 * @param int $statusCode
	 */
	public function __construct(int $statusCode = 302) {
		parent::__construct($statusCode);
	}
}
