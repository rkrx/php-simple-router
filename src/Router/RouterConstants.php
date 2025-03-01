<?php
namespace Kir\Http\Routing\Router;

class RouterConstants {
	public const ERROR_UNKNOWN = 400;
	public const ERROR_ROUTE_NOT_FOUND = 401;
	public const ERROR_METHOD_NOT_REGISTERED = 402;

	public static function getMessage(int $code): string {
		return match ($code) {
			self::ERROR_ROUTE_NOT_FOUND => 'Route not found',
			self::ERROR_METHOD_NOT_REGISTERED => 'Method not registered',
			default => 'Unknown error',
		};
	}
}
