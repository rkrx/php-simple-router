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

	public static function createServerRequest(): ServerRequest {
		/** @var array{HTTPS?: string, HTTP_HOST?: string, REQUEST_METHOD: string, SERVER_NAME?: string, SERVER_PORT?: string|int, REQUEST_URI?: string, QUERY_STRING?: string, CONTENT_TYPE?: string} $serverVars */
		$serverVars = $_SERVER;

		$scheme = (($serverVars['HTTPS'] ?? 'off') !== 'off') ? 'https' : 'http';
		$host = $serverVars['HTTP_HOST'] ?? $serverVars['SERVER_NAME'] ?? 'localhost';
		$port = $serverVars['SERVER_PORT'] ?? null;
		$path = $serverVars['REQUEST_URI'] ?? '/';

		$uri = new Uri(sprintf('%s://%s%s%s', $scheme, $host, ($port && !in_array($port, [80, 443]) ? ":$port" : ''), $path));

		/** @var array<string, mixed> $queryParams */
		$queryParams = $_GET;

		$parsedBody = $_POST;

		if(str_contains($serverVars['CONTENT_TYPE'] ?? '', 'application/json')) {
			$json = file_get_contents('php://input');
			if($json === false) {
				throw new RuntimeException('Invalid input');
			}
			$parsedBody = json_decode(json: $json, associative: true, depth: 512, flags: JSON_THROW_ON_ERROR);
		}

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
	 * @param callable $handler
	 * @return $this
	 * @throws ImmutableProperty
	 * @throws RouteAlreadyExists
	 */
	public function add(string $name, array $methods, string $pattern, $handler): self {
		$this->router->getMap()->route(name: $name, path: $pattern, handler: $handler)->allows($methods);
		return $this;
	}

	/**
	 * @param string $name
	 * @param string $pattern
	 * @param callable $handler
	 * @return $this
	 */
	public function get(string $name, string $pattern, $handler) {
		$this->add(name: $name, methods: ['GET'], pattern: $pattern, handler: $handler);
		return $this;
	}

	/**
	 * @param string $name
	 * @param string $pattern
	 * @param callable $handler
	 * @return $this
	 */
	public function post(string $name, string $pattern, $handler) {
		$this->add(name: $name, methods: ['POST'], pattern: $pattern, handler: $handler);
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

		return new Route(
			name: $route->name,
			method: $request->getMethod(),
			params: $attributes,
			handler: $handler
		);
	}
}
