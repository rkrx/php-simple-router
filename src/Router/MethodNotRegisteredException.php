<?php
namespace Kir\Http\Routing\Router;

use Exception;

class MethodNotRegisteredException extends RouterException {
	/**
	 * @param string $method
	 * @param string $url
	 * @param int $code
	 * @param Exception $previousException
	 */
	public function __construct($method, $url, $code = RouterConstants::ERROR_METHOD_NOT_REGISTERED, Exception $previousException = null) {
		parent::__construct($method, $url, $code, $previousException);
	}
}