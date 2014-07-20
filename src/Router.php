<?php
namespace Kir\Http\Routing;

class Router {
	/**
	 * @var string[]
	 */
	private $routePatterns;

	/**
	 * @param array $routes
	 */
	public function __construct(array $routes) {
		$this->routePatterns = $this->compile($routes);
	}

	/**
	 * @param string $requestUri
	 * @param string $method
	 * @return array
	 */
	public function lookup($requestUri, $method) {
		$key = sprintf('%s %s', strtoupper($method), $requestUri);
		foreach($this->routePatterns as $routePattern => $data) {
			$matches = array();
			if(preg_match($routePattern, $key, $matches)) {
				$data['params'] = $matches;
				return $data;
			}
		}
		return array();
	}

	/**
	 * @param array $routes
	 * @return array
	 */
	private function compile(array $routes) {
		$result = array();
		foreach($routes as $route => $data) {
			$route = preg_quote($route, '/');
			$route = preg_replace_callback('/\\\\:\\w+/', function ($input) {
				$key = $input[0];
				$key = ltrim($key, '\\:');
				return "(?P<{$key}>\\w+)";
			}, $route);
			$result["/^{$route}$/"] = $data;
		}
		$result = $this->sortRoutes($result);
		return $result;
	}

	/**
	 * @param array $result
	 * @return array
	 */
	private function sortRoutes(array $result) {
		uksort($result, function ($a, $b) {
			return strlen($a) > strlen($b) ? 1 : (strlen($a) < strlen($b) ? -1 : 0);
		});
		return $result;
	}
}