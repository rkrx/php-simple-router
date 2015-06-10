<?php
namespace Kir\Http\Routing\Http;

class HttpRedirect {
	/**
	 * @param $address
	 * @param int $type
	 * @param bool $exitAfterCall
	 */
	public function to($address, $type = 302, $exitAfterCall = true) {
		header(sprintf('location: %s', $address), true, $type);
		if($exitAfterCall) {
			exit;
		}
	}
}