<?php
namespace Kir\Http\Routing\Tools;

/**
 * @param mixed|array $a
 * @param mixed|array $b
 * @param array|null $mask
 * @return array
 */
function merge($a, $b, $mask = null) {
	if(!is_array($a)) {
		$a = array();
	}
	if(!is_array($b)) {
		$b = array();
	}
	if(is_array($mask)) {
		$b = intersectKeys($b, $mask);
	}
	return array_merge($a, $b);
}

/**
 * @param array $array
 * @param array $keys
 * @return array
 */
function intersectKeys(array $array, array $keys) {
	$result = array();
	foreach($keys as $key) {
		if(array_key_exists($key, $array)) {
			$result[$key] = $array[$key];
		}
	}
	return $result;
}