<?php
namespace Kir\Http\Routing\Exceptions;

use Exception;
use Kir\Http\Routing\Router\RouterConstants;
use Throwable;

abstract class RouterException extends Exception {
	public function __construct(
		public readonly string $url,
		public readonly string $method,
		int $code = RouterConstants::ERROR_UNKNOWN,
		?Throwable $previousException = null
	) {
		$message = RouterConstants::getMessage($code);
		parent::__construct($message, $code, $previousException);
	}

	/**
	 * @return string
	 */
	public function getMethod(): string {
		return $this->method;
	}

	/**
	 * @return string
	 */
	public function getUrl(): string {
		return $this->url;
	}
}
