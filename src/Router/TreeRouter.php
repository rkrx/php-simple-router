<?php
// This class comes from https://github.com/baryshev/TreeRoute
// Copyright (c) 2015, Vadim Baryshev

namespace Kir\Http\Routing\Router;

class TreeRouter {
	const PARAM_REGEXP = '/^{((([^:]+):(.+))|(.+))}$/';
	const SEPARATOR_REGEXP = '/^[\s\/]+|[\s\/]+$/';

	private $routes = ['childs' => [], 'regexps' => []];

	public function match($url) {
		$parts = explode('?', $url, 1);
		$parts = explode('/', preg_replace(self::SEPARATOR_REGEXP, '', $parts[0]));
		if(sizeof($parts) === 1 && $parts[0] === '') {
			$parts = [];
		}
		$params = [];
		$current = $this->routes;

		for($i = 0, $length = sizeof($parts); $i < $length; $i++) {
			if(isset($current['childs'][$parts[$i]])) {
				$current = $current['childs'][$parts[$i]];
			} else {
				foreach($current['regexps'] as $regexp => $route) {
					if(preg_match('/^' . addcslashes($regexp, '/') . '$/', $parts[$i])) {
						$current = $route;
						$params[$current['name']] = $parts[$i];
						continue 2;
					}
				}
				if(isset($current['others'])) {
					$current = $current['others'];
					$params[$current['name']] = $parts[$i];
				} else {
					return null;
				}
			}
		}

		if(!isset($current['methods'])) {
			return null;
		} else {
			return [
				'methods' => $current['methods'],
				'route' => $current['route'],
				'params' => $params
			];
		}
	}

	public function addRoute($methods, $route, $handler) {
		if(is_string($methods)) {
			$methods = [$methods];
		}

		$parts = explode('?', $route, 1);
		$parts = explode('/', preg_replace(self::SEPARATOR_REGEXP, '', $parts[0]));
		if(sizeof($parts) === 1 && $parts[0] === '') {
			$parts = [];
		}

		$current = &$this->routes;
		for($i = 0, $length = sizeof($parts); $i < $length; $i++) {
			$paramsMatch = preg_match(self::PARAM_REGEXP, $parts[$i], $paramsMatches);
			if($paramsMatch) {
				if(!empty($paramsMatches[2])) {
					if(!isset($current['regexps'][$paramsMatches[4]])) {
						$current['regexps'][$paramsMatches[4]] = [
							'childs' => [],
							'regexps' => [],
							'name' => $paramsMatches[3]
						];
					}
					$current = &$current['regexps'][$paramsMatches[4]];
				} else {
					if(!isset($current['others'])) {
						$current['others'] = ['childs' => [], 'regexps' => [], 'name' => $paramsMatches[5]];
					}
					$current = &$current['others'];
				}
			} else {
				if(!isset($current['childs'][$parts[$i]])) {
					$current['childs'][$parts[$i]] = ['childs' => [], 'regexps' => []];
				}
				$current = &$current['childs'][$parts[$i]];
			}
		}

		$current['route'] = $route;
		for($i = 0, $length = sizeof($methods); $i < $length; $i++) {
			if(!isset($current['methods'])) {
				$current['methods'] = [];
			}
			$current['methods'][strtoupper($methods[$i])] = $handler;
		}
	}

	public function getOptions($url) {
		$route = $this->match($url);
		if(!$route) {
			return null;
		} else {
			return array_keys($route['methods']);
		}
	}

	public function dispatch($method, $url) {
		$route = $this->match($url);

		if(!$route) {
			return [
				'error' => [
					'code' => 404,
					'message' => 'Not Found'
				],
				'method' => $method,
				'url' => $url
			];
		} else {
			if(isset($route['methods'][$method])) {
				return [
					'method' => $method,
					'url' => $url,
					'route' => $route['route'],
					'params' => $route['params'],
					'handler' => $route['methods'][$method]
				];
			} else {
				return [
					'error' => [
						'code' => 405,
						'message' => 'Method Not Allowed'
					],
					'method' => $method,
					'url' => $url,
					'route' => $route['route'],
					'params' => $route['params'],
					'allowed' => array_keys($route['methods'])
				];
			}
		}
	}

	public function getRoutes() {
		return $this->routes;
	}

	public function setRoutes($routes) {
		$this->routes = $routes;
	}

	public function options($route, $handler) {
		$this->addRoute('OPTIONS', $route, $handler);
	}

	public function get($route, $handler) {
		$this->addRoute('GET', $route, $handler);
	}

	public function head($route, $handler) {
		$this->addRoute('HEAD', $route, $handler);
	}

	public function post($route, $handler) {
		$this->addRoute('POST', $route, $handler);
	}

	public function put($route, $handler) {
		$this->addRoute('PUT', $route, $handler);
	}

	public function delete($route, $handler) {
		$this->addRoute('DELETE', $route, $handler);
	}

	public function trace($route, $handler) {
		$this->addRoute('TRACE', $route, $handler);
	}

	public function connect($route, $handler) {
		$this->addRoute('CONNECT', $route, $handler);
	}
}
