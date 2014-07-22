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
		$this->routePatterns = $this->compile($routes);
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
				$data['params'] = $matches;
				return $data;
			}
		}
		return array('params' => array());
	}

	/**
	 * @param array $routes
	 * @return array
	 */
	private function compile(array $routes) {
		$result = array();
		foreach($routes as $route => $data) {
			$route = preg_quote($route, '/');
			$route = preg_replace_callback('/\\\\\\[(.*?)\\\\\\]/', function ($input) {
				return "(?:{$input[1]})?";
			}, $route);
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
		$pos = strpos($requestUri, '?');
		if($pos !== false) {
			$requestUri = substr($requestUri, 0, $pos);
		}
		return $requestUri;
	}
}