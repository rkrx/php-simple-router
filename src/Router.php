<?php
namespace Kir\Http\Routing;

class Router {
	/**
	 * @var array[]
	 */
	private $routes;

	/**
	 * @var array[]
	 */
	private $routePatterns;

	/**
	 * @param array $routes
	 */
	public function __construct(array $routes) {
		$this->routes = $routes;
		$this->routePatterns = $this->compileRoutes($routes);
	}

	/**
	 * @return mixed
	 */
	public function getRoutes() {
		return $this->routes;
	}

	/**
	 * @param string $requestUri
	 * @param string $method
	 * @return array
	 */
	public function lookup($requestUri, $method) {
		$requestUri = $this->extractPath($requestUri);
		$key = sprintf('%s %s', strtoupper($method), $requestUri);
		foreach($this->routePatterns as $routePattern => $data) {
			$matches = array();
			if(preg_match($routePattern, $key, $matches)) {
				$matches = $this->filterNumericKeys($matches);
				return array('data' => $data, 'params' => $matches);
			}
		}
		return array('data' => null, 'params' => array());
	}

	/**
	 * @param array $routes
	 * @return array
	 */
	private function compileRoutes(array $routes) {
		$compiledRoutes = array();
		$routes = $this->sortRoutes($routes);
		foreach($routes as $route => $data) {
			$route = $this->compileRoute($route);
			$compiledRoutes[$route] = $data;
		}
		return $compiledRoutes;
	}

	/**
	 * @param string $route
	 * @return string
	 */
	private function compileRoute($route) {
        	$route = preg_quote($route, '/');
        	$route = str_replace(array('\[', '\]'), array('(?:', ')?'), $route);
        	$route = preg_replace('/(?:\\\:(\w+))/', '(?P<$1>\\w+)', $route);
        	return "/^{$route}$/";
	}

	/**
	 * @param array $result
	 * @return array
	 */
	private function sortRoutes(array $result) {
		uksort($result, function ($a, $b) {
			return strlen($a) < strlen($b) ? 1 : (strlen($a) > strlen($b) ? -1 : 0);
		});
		return $result;
	}

	/**
	 * @param array $matches
	 * @return array
	 */
	private function filterNumericKeys($matches) {
		$result = array();
		foreach($matches as $key => $value) {
			if(!is_numeric($key)) {
				$result[$key] = $value;
			}
		}
		return $result;
	}

	/**
	 * @param string $requestUri
	 * @return string
	 */
	private function extractPath($requestUri) {
		list($path) = explode('?', $requestUri, 2);
		return $path;
	}
}
