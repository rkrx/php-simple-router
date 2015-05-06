<?php
namespace Kir\Http\Routing;

use ArrayAccess;
use Kir\Http\Routing\PatternConverter\PatternConverter;

class LookupTable implements ArrayAccess {
	/** @var array[] */
	private $routes;
	/** @var callable[] */
	private $listeners = array();
	/** @var PatternConverter */
	private $patternConverter;

	/**
	 * @param PatternConverter $patternConverter
	 */
	public function __construct(PatternConverter $patternConverter = null) {
		$this->patternConverter = $patternConverter;
	}

	/**
	 * @return mixed
	 */
	public function getRoutes() {
		return $this->routes;
	}

	/**
	 * @param string $key
	 * @return array
	 */
	public function lookup($key) {
		foreach($this->routes as $pattern => $routeData) {
			if(!array_key_exists('pattern', $routeData)) {
				$routeData['pattern'] = $this->patternConverter->convert($pattern);
			}
			print_r($pattern);
			print_r($routeData);
			$matches = array();
			$params = array();
			if(preg_match($routeData['pattern'], $key, $matches)) {
				$matches = array_intersect_key($matches, array_flip(array_filter(array_keys($matches), 'ctype_alpha')));
				$params = array_merge($params, $matches);
				return array('data' => $routeData['data'], 'params' => $params);
			}
		}
		return array('data' => null, 'params' => array());
	}

	/**
	 * @param string $offset
	 * @return boolean
	 */
	public function offsetExists($offset) {
		return array_key_exists($offset, $this->routes);
	}

	/**
	 * @param string $offset
	 * @return mixed Can return all value types.
	 */
	public function offsetGet($offset) {
		if($this->offsetExists($offset)) {
			return $this->routes[$offset]['data'];
		}
		return null;
	}

	/**
	 * @param string $offset
	 * @param mixed $value
	 * @return $this
	 */
	public function offsetSet($offset, $value) {
		$this->routes[$offset] = array(
			'data' => $value,
		);
		return $this;
	}

	/**
	 * @param string $offset
	 * @return $this
	 */
	public function offsetUnset($offset) {
		unset($this->routes[$offset]);
		return $this;
	}
}
