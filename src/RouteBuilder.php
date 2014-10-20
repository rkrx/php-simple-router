<?php
namespace Kir\Http\Routing;

class RouteBuilder {
	/**
	 * @param string $pattern
	 * @param array $params
	 * @return string
	 */
	public function buildRoute($pattern, array $params = array()) {
		if(preg_match('/^[A-Z]+\\s+(.*)$/', $pattern, $matches)) {
			$pattern = $matches[1];
		}
		$keys = array();
		foreach($params as $key => $value) {
			$keys['{'.$key.'}'] = $value;
		}
		return strtr($pattern, $keys);
	}
}