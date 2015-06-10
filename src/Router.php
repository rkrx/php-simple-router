<?php
namespace Kir\Http\Routing;

use Exception;
use Ioc\MethodInvoker;
use Kir\Http\Routing\Router\DebugOutput;
use Kir\Http\Routing\Router\MethodNotRegisteredException;
use Kir\Http\Routing\Router\RouteNotFoundException;
use Kir\Http\Routing\Router\RouterConstants;
use Kir\Http\Routing\Router\TreeRouter;

class Router {
	/** @var TreeRouter */
	private $router;
	/** @var MethodInvoker */
	private $methodInvoker;
	/** @var callable */
	private $postProcessor = null;
	/** @var callable */
	private $errorHandler;
	/** @var bool */
	private $debugMode;

	/**
	 * @param MethodInvoker $methodInvoker
	 */
	public function __construct(MethodInvoker $methodInvoker) {
		$this->router = new TreeRouter();
		$this->methodInvoker = $methodInvoker;

		$this->setErrorHandler(function ($reason, $url, $method, Exception $exception = null) {
			switch ($reason) {
				case RouterConstants::ERROR_ROUTE_NOT_FOUND:
					http_response_code(404);
					DebugOutput::show($reason, $url, $method, $exception);
					exit;
				case RouterConstants::ERROR_METHOD_NOT_REGISTERED:
					http_response_code(404);
					DebugOutput::show($reason, $url, $method, $exception);
					exit;
				case RouterConstants::ERROR_UNKNOWN:
					http_response_code(500);
					DebugOutput::show($reason, $url, $method, $exception);
					exit;
			}
		});
	}

	/**
	 * @return boolean
	 */
	public function isDebugMode() {
		return $this->debugMode;
	}

	/**
	 * @param boolean $debugMode
	 * @return $this
	 */
	public function setDebugMode($debugMode) {
		$this->debugMode = $debugMode;
		return $this;
	}

	/**
	 * @param callable $postProcessor
	 * @return $this
	 */
	public function setPostProcessor($postProcessor) {
		$this->postProcessor = $postProcessor;
		return $this;
	}

	/**
	 * @param callable $errorHandler
	 * @return $this
	 */
	public function setErrorHandler($errorHandler) {
		$this->errorHandler = $errorHandler;
		return $this;
	}

	/**
	 * @param array $methods
	 * @param string $pattern
	 * @param callable $callback
	 * @param array $params
	 * @return $this
	 */
	public function add(array $methods, $pattern, $callback, array $params = array()) {
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
	public function get($pattern, $callback, array $params = array()) {
		$this->add(['GET'], $pattern, $callback, $params);
		return $this;
	}

	/**
	 * @param string $pattern
	 * @param callable $callback
	 * @param array $params
	 * @return $this
	 */
	public function post($pattern, $callback, array $params = array()) {
		$this->add(['POST'], $pattern, $callback, $params);
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
			throw new RouteNotFoundException($method, $url);
		}
		if(!array_key_exists($method, $route['methods'])) {
			throw new MethodNotRegisteredException($method, $url);
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
		try {
			$response = $this->getResponse($method, $url);
			if(is_scalar($response)) {
				echo $response;
			}
			http_response_code(200);
		} catch(RouteNotFoundException $e) {
			$this->callErrorHandler(RouterConstants::ERROR_ROUTE_NOT_FOUND, $method, $url, $e);
		} catch(MethodNotRegisteredException $e) {
			$this->callErrorHandler(RouterConstants::ERROR_METHOD_NOT_REGISTERED, $method, $url, $e);
		} catch(Exception $e) {
			$this->callErrorHandler(RouterConstants::ERROR_UNKNOWN, $method, $url, $e);
			exit;
		}
	}

	/**
	 * @param int $reason
	 * @param string $method
	 * @param string $url
	 * @param Exception $e
	 */
	private function callErrorHandler($reason, $method, $url, Exception $e) {
		$data = [
			'reason' => $reason,
			'method' => $method,
			'url' => $url,
			'exception' => $e
		];
		$this->methodInvoker->invoke($this->errorHandler, $data);
	}
}