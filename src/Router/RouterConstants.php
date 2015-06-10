<?php
namespace Kir\Http\Routing\Router;

class RouterConstants {
	const ERROR_UNKNOWN = 400;
	const ERROR_ROUTE_NOT_FOUND = 401;
	const ERROR_METHOD_NOT_REGISTERED = 402;

	public static function getMessage($code) {
		switch ($code) {
			case self::ERROR_ROUTE_NOT_FOUND:
				return 'Method not registered';
			case self::ERROR_METHOD_NOT_REGISTERED:
				return 'Method not registered';
			default:
				return 'Unknown error';
		}
	}
}