<?php
namespace Kir\Http\Routing;

use Aura\Router\Exception\ImmutableProperty;
use Aura\Router\Exception\RouteAlreadyExists;
use Aura\Router\RouterContainer;
use Kir\Http\Routing\Common\Response;
use Kir\Http\Routing\Common\Route;
use Kir\Http\Routing\Common\ServerRequest;
use Kir\Http\Routing\Common\Stream;
use Kir\Http\Routing\Common\Uri;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class Router {
	private readonly RouterContainer $router;

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

	public function __construct() {
		$this->router = new RouterContainer();
	}

	/**
	 * @param string $name
	 * @param string[] $methods
	 * @param string $pattern
	 * @param callable|array<string, mixed>|object $params
	 * @return $this
	 * @throws ImmutableProperty
	 * @throws RouteAlreadyExists
	 */
	public function add(string $name, array $methods, string $pattern, $params): self {
		$this->router->getMap()->route(name: $name, path: $pattern, handler: fn() => $params)->allows($methods);
		return $this;
	}

	/**
	 * @param string $name
	 * @param string $pattern
	 * @param callable|array<string, mixed>|object $params
	 * @return $this
	 */
	public function get(string $name, string $pattern, $params) {
		$this->add(name: $name, methods: ['GET'], pattern: $pattern, params: $params);
		return $this;
	}

	/**
	 * @param string $name
	 * @param string $pattern
	 * @param callable|array<string, mixed>|object $params
	 * @return $this
	 */
	public function post(string $name, string $pattern, $params) {
		$this->add(name: $name, methods: ['POST'], pattern: $pattern, params: $params);
		return $this;
	}

	/**
	 * @param string $name
	 * @param string $pattern
	 * @param callable|array<string, mixed>|object $params
	 * @return $this
	 */
	public function put(string $name, string $pattern, $params) {
		$this->add(name: $name, methods: ['PUT'], pattern: $pattern, params: $params);
		return $this;
	}

	/**
	 * @param string $name
	 * @param string $pattern
	 * @param callable|array<string, mixed>|object $params
	 * @return $this
	 */
	public function delete(string $name, string $pattern, $params) {
		$this->add(name: $name, methods: ['DELETE'], pattern: $pattern, params: $params);
		return $this;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @return Route|null
	 */
	public function lookup(ServerRequestInterface $request): ?Route {
		$route = $this->router->getMatcher()->match($request);
		if($route === false) {
			return null;
		}

		/** @var array<string, mixed> $attributes */
		$attributes = $route->attributes;

		/** @var callable $handler */
		$handler = $route->handler;

		/** @var callable|array<string, mixed>|object $params */
		$params = $handler();

		return new Route(
			name: $route->name,
			method: $request->getMethod(),
			attributes: $attributes,
			params: $params
		);
	}
}
