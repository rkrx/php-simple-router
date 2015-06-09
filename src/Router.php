<?php
namespace Kir\Http\Routing;

use Exception;
use Ioc\MethodInvoker;
use Kir\Http\Routing\Router\MethodNotRegisteredException;
use Kir\Http\Routing\Router\RouteNotFoundException;
use Kir\Http\Routing\Router\TreeRouter;

class Router {
	/** @var TreeRouter */
	private $router;
	/** @var MethodInvoker */
	private $methodInvoker;
	/** @var callable */
	private $postProcessor = null;

	/**
	 * @param MethodInvoker $methodInvoker
	 */
	public function __construct(MethodInvoker $methodInvoker) {
		$this->router = new TreeRouter();
		$this->methodInvoker = $methodInvoker;
	}

	/**
	 * @param callable $postProcessor
	 * @return $this
	 */
	public function setPostProcessor(callable $postProcessor) {
		$this->postProcessor = $postProcessor;
		return $this;
	}

	/**
	 * @param array $methods
	 * @param string $pattern
	 * @param callable $callback
	 * @param array $params
	 * @return $this
	 */
	public function add(array $methods, $pattern, callable $callback, array $params = array()) {
		$methods = array_map('strtoupper', $methods);
		$this->router->addRoute($methods, $pattern, [
			'callback' => $callback,
			'params' => $params
		]);
		return $this;
	}

	/**
	 * @param string $pattern
	 * @param callable $callback
	 * @param array $params
	 * @return $this
	 */
	public function get($pattern, callable $callback, array $params = array()) {
		$this->add(['GET'], $pattern, $callback, $params);
		return $this;
	}

	/**
	 * @param string $pattern
	 * @param callable $callback
	 * @param array $params
	 * @return $this
	 */
	public function post($pattern, callable $callback, array $params = array()) {
		$this->add(['GET'], $pattern, $callback, $params);
		return $this;
	}

	/**
	 * @param string $url
	 * @return array|null
	 */
	public function lookup($url) {
		$data = $this->router->match($url);
		return $data;
	}

	/**
	 * @param string $method
	 * @param string $url
	 * @return mixed
	 * @throws Exception
	 */
	public function getResponse($method, $url) {
		$route = $this->lookup($url);
		if($route === null) {
			throw new RouteNotFoundException('Route not found');
		}
		if(!array_key_exists($method, $route['methods'])) {
			throw new MethodNotRegisteredException('Route not found');
		}
		$routeData = $route['methods'][$method];
		$result = $this->methodInvoker->invoke($routeData['callback'], $route['params']);
		if($this->postProcessor !== null) {
			$result = $this->methodInvoker->invoke($this->postProcessor, array_merge($routeData['params'], ['result' => $result]));
		}
		return $result;
	}

	/**
	 * @param string $method
	 * @param string $url
	 * @return mixed
	 * @throws Exception
	 */
	public function dispatch($method, $url) {
		http_response_code(500);
		try {
			$response = $this->getResponse($method, $url);
			if(is_scalar($response)) {
				echo $response;
			}
			http_response_code(200);
		} catch(RouteNotFoundException $e) {
			http_response_code(404);
			exit;
		} catch(MethodNotRegisteredException $e) {
			http_response_code(404);
			exit;
		} catch(Exception $e) {
			exit;
		}
	}
}