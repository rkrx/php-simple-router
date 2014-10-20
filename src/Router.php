<?php
namespace Kir\Http\Routing;

use ArrayAccess;

class Router implements ArrayAccess {
	/**
	 * @var array[]
	 */
	private $routes;

	/**
	 * @var callable[]
	 */
	private $listeners = array();

	/**
	 * @param array $routes
	 */
	public function __construct(array $routes = array()) {
		$this->routes = $routes;
		foreach($routes as $route => $data) {
			$this->offsetSet($route, $data);
		}
	}

	/**
	 * @return mixed
	 */
	public function getRoutes() {
		return $this->routes;
	}

	/**
	 * @param string $request
	 * @param string $method
	 * @return array
	 */
	public function lookup($request, $method) {
		$key = sprintf('%s %s', strtoupper($method), $request);
		foreach($this->routes as $routeData) {
			$matches = array();
			if(preg_match($routeData['pattern'], $key, $matches)) {
				$matches = array_intersect_key($matches, array_flip(array_filter(array_keys($matches), 'ctype_alpha')));
				return array('data' => $routeData['data'], 'params' => $matches);
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
			'pattern' => $this->compileRoute($offset),
			'data' => $value
		);
		$this->routes = $this->sortRoutes($this->routes);
		$this->fireEvent($offset, $value);
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
			call_user_func($fn, $pattern, $data);
		}
		return $this;
	}

	/**
	 * @param string $route
	 * @return string
	 */
	private function compileRoute($route) {
        	$route = preg_quote($route, '/');
        	$route = str_replace(array('\\[', '\\]'), array('(?:', ')?'), $route);
        	$route = preg_replace('/(?:\\\:(\w+))/', '(?P<$1>\\w+)', $route);
        	return "/^{$route}$/";
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
			call_user_func($listener, $pattern, $data);
		}
		return $this;
	}
}
