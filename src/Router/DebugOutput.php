<?php
namespace Kir\Http\Routing\Router;

use Exception;

/**
 * For internal purpose only
 */
class DebugOutput {
	/**
	 * @param int $reason
	 * @param string $url
	 * @param string $method
	 * @param Exception $exception
	 */
	public static function show($reason, $url, $method, Exception $exception = null) {
		if(php_sapi_name() === 'cli') {
			self::showCli($reason, $url, $method, $exception);
		} else {
			self::showHtml($reason, $url, $method, $exception);
		}
	}

	/**
	 * @param int $reason
	 * @param string $url
	 * @param string $method
	 * @param Exception $exception
	 */
	private static function showHtml($reason, $url, $method, Exception $exception = null) {
		printf("<h1>%s</h1>\n", RouterConstants::getMessage($reason));
		printf("<p>%s %s</p>\n\n", $method, $url);
		printf("<div>Exception: %s (%d)<div>\n", $exception->getMessage(), $exception->getCode());
		printf("<div>%s (%d)</div>\n\n", $exception->getFile(), $exception->getLine());
		printf("<p><pre>%s</pre></p>\n", $exception->getTraceAsString());
	}

	/**
	 * @param int $reason
	 * @param string $url
	 * @param string $method
	 * @param Exception $exception
	 */
	private static function showCli($reason, $url, $method, Exception $exception = null) {
		printf("Reason: %s\n", RouterConstants::getMessage($reason));
		printf("URL: %s %s\n", $method, $url);
		printf("Exception: %s (%d)\n", $exception->getMessage(), $exception->getCode());
		printf("%s (%d)\n\n", $exception->getFile(), $exception->getLine());
		printf("%s\n", $exception->getTraceAsString());
	}
}