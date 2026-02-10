<?php
namespace Kir\Http\Routing;

use Kir\Http\Routing\Common\ServerRequest;
use Kir\Http\Routing\Common\Response;
use Kir\Http\Routing\Common\Stream;
use Kir\Http\Routing\Common\Uri;
use RuntimeException;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route as SymfonyRoute;

class RouterBuilder {
	private RouteCollection $routes;

	public function __construct() {
		$this->routes = new RouteCollection();
	}

	public function build(): Router {
		return new Router($this->routes);
	}

	public static function createServerRequestFromEnv(?Uri $uri = null): ServerRequest {
		/** @var array<string, mixed> $queryParams */
		$queryParams = $_GET;

		$parsedBody = $_POST;

		/** @var array{REQUEST_METHOD: string, CONTENT_TYPE?: string} $serverVars */
		$serverVars = $_SERVER;

		if(str_contains($serverVars['CONTENT_TYPE'] ?? '', 'application/json')) {
			$json = file_get_contents('php://input');
			if($json === false) {
				throw new RuntimeException('Invalid input');
			}
			$parsedBody = json_decode(json: $json, associative: true, depth: 512, flags: JSON_THROW_ON_ERROR);
		}

		$uri ??= new Uri(is_string($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');

		return new ServerRequest(
			method: $serverVars['REQUEST_METHOD'],
			uri: $uri,
			queryParams: $queryParams,
			parsedBody: $parsedBody
		);
	}

	public static function createResponse(): Response {
		return new Response(body: new Stream);
	}

	/**
	 * @param string $name
	 * @param string[] $methods
	 * @param string $pattern
	 * @param callable $callable
	 * @param array<string, mixed>|object $params
	 * @return $this
	 */
	public function add(string $name, array $methods, string $pattern, $callable, $params): self {
		$route = new SymfonyRoute(
			path: $pattern,
			defaults: ['callable' => $callable],
			requirements: [],
			options: ['params' => $params],
			host: '',
			methods: $methods,
			condition: '',
		);

		$this->routes->add($name, $route);

		return $this;
	}

	/**
	 * @param array{routes?: array<int, array<string, mixed>>} $definitions
	 * @return $this
	 */
	public function addDefinitions(array $definitions): self {
		/** @var array<int, array<string, mixed>> $routes */
		$routes = $definitions['routes'] ?? [];

		foreach($routes as $route) {
			if(!is_array($route)) { // @phpstan-ignore-line
				continue;
			}

			$name = $route['name'] ?? null;
			$pattern = $route['path'] ?? null;

			/** @var null|(callable(): mixed) $callable */
			$callable = $route['target'] ?? ($route['callable'] ?? null);

			/** @var array<string, mixed>|object $params */
			$params = $route['params'] ?? [];

			if(!is_string($name) || !is_string($pattern) || $callable === null) {
				continue;
			}

			$methodsValue = $route['method'] ?? ($route['methods'] ?? ['GET']);
			$methods = is_array($methodsValue) ? array_values($methodsValue) : [$methodsValue];
			$methods = array_values(array_filter($methods, static fn($method) => is_string($method) && $method !== ''));

			if($methods === []) {
				$methods = ['GET'];
			}

			$this->add(
				name: $name,
				methods: $methods,
				pattern: $pattern,
				callable: $callable,
				params: $params
			);
		}

		return $this;
	}

	/**
	 * @param string $name
	 * @param string $pattern
	 * @param callable $callable
	 * @param array<string, mixed>|object $params
	 * @return $this
	 */
	public function get(string $name, string $pattern, $callable, $params) {
		$this->add(
			name: $name,
			methods: ['GET'],
			pattern: $pattern,
			callable: $callable,
			params: $params
		);
		return $this;
	}

	/**
	 * @param string $name
	 * @param string $pattern
	 * @param callable $callable
	 * @param array<string, mixed>|object $params
	 * @return $this
	 */
	public function post(string $name, string $pattern, $callable, $params) {
		$this->add(
			name: $name,
			methods: ['POST'],
			pattern: $pattern,
			callable: $callable,
			params: $params
		);
		return $this;
	}

	/**
	 * @param string $name
	 * @param string $pattern
	 * @param callable $callable
	 * @param array<string, mixed>|object $params
	 * @return $this
	 */
	public function put(string $name, string $pattern, $callable, $params) {
		$this->add(
			name: $name,
			methods: ['PUT'],
			pattern: $pattern,
			callable: $callable,
			params: $params
		);
		return $this;
	}

	/**
	 * @param string $name
	 * @param string $pattern
	 * @param callable $callable
	 * @param array<string, mixed>|object $params
	 * @return $this
	 */
	public function delete(string $name, string $pattern, $callable, $params) {
		$this->add(
			name: $name,
			methods: ['DELETE'],
			pattern: $pattern,
			callable: $callable,
			params: $params
		);
		return $this;
	}
}
