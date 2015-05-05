<?php
namespace Kir\Http\Routing;

use ArrayAccess;

class LookupTable implements ArrayAccess {
	/** @var array[] */
	private $routes;

	/** @var callable[] */
	private $listeners = array();
	/**
	 * @var PatternConverter
	 */
	private $patternConverter;

	/**
	 * @param PatternConverter $patternConverter
	 * @internal param array $routes
	 */
	public function __construct(PatternConverter $patternConverter = null) {
		$this->routes = $routes;
		foreach($routes as $route => $data) {
			$this->offsetSet($route, $data);
		}
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
		foreach($this->routes as $routeData) {
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
		list($path, $queryParams) = $this->splitOffset($offset);
		$this->routes[$offset] = array(
			'pattern' => $this->compileRoute($path),
			'data' => $value,
			'queryParams' => $queryParams
		);
		$this->routes = $this->sortRoutes($this->routes);
		$this->fireEvent($offset, $this->routes[$offset]);
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

	/**
	 * @param callable $fn
	 * @return $this
	 */
	public function addNewRouteListener($fn) {
		$this->listeners[] = $fn;
		foreach($this->routes as $pattern => $data) {
			call_user_func($fn, $data, $pattern);
		}
		return $this;
	}

	/**
	 * @param string $route
	 * @return string
	 */
	private function compileRoute($route) {
		# '/(?:(?<!(?:\\x5c))(?:(?:\\x5c){2})*?(?:\[|\]))/'

		$route = preg_quote($route, '/');
		$route = str_replace(array('\\[', '\\]'), array('(?:', ')?'), $route);
		$route = preg_replace('/(?:\\:(\w+))/', '(?P<$1>\\w+)', $route);
		$pattern = "/^{$route}$/";
		#var_dump($pattern);
		return $pattern;
	}

	/**
	 * @param array $routes
	 * @return array
	 */
	private function sortRoutes(array $routes) {
		uksort($routes, function ($a, $b) {
			return strlen($a) < strlen($b) ? 1 : (strlen($a) > strlen($b) ? -1 : 0);
		});
		return $routes;
	}

	/**
	 * @param string $pattern
	 * @param mixed $data
	 * @return $this
	 */
	private function fireEvent($pattern, $data) {
		foreach($this->listeners as $listener) {
			call_user_func($listener, $data, $pattern);
		}
		return $this;
	}

	/**
	 * @param string $offset
	 * @return array
	 */
	private function splitOffset($offset) {
		list($path, $queryParams) = explode('?', "{$offset}?", 2);
		$queryParams = rtrim($queryParams, '?');
		parse_str($queryParams, $queryParams);
		return array($path, $queryParams);
	}
}
