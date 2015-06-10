<?php
namespace Kir\Http\Routing\Router;

use Exception;

class RouteNotFoundException extends RouterException {
	/**
	 * @param string $method
	 * @param string $url
	 * @param int $code
	 * @param Exception $previousException
	 */
	public function __construct($method, $url, $code = RouterConstants::ERROR_ROUTE_NOT_FOUND, Exception $previousException = null) {
		parent::__construct($method, $url, $code);
	}
}