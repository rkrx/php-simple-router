<?php
namespace Kir\Http\Routing;

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
