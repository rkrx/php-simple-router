<?php
namespace Kir\Http\Routing\Router;

use Exception;

class RouterException extends Exception {
	/** @var string */
	private $method;
	/** @var string */
	private $url;

	/**
	 * @param string $method
	 * @param string $url
	 * @param int $code
	 * @param Exception $previousException
	 */
	public function __construct($method, $url, $code = RouterConstants::ERROR_UNKNOWN, Exception $previousException = null) {
		$message = RouterConstants::getMessage($code);
		parent::__construct($message, $code, $previousException);
		$this->method = $method;
		$this->url = $url;
	}

	/**
	 * @return string
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}
}