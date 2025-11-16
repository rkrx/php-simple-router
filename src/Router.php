<?php
namespace Kir\Http\Routing;

use Kir\Http\Routing\Common\Response;
use Kir\Http\Routing\Common\Route;
use Kir\Http\Routing\Common\ServerRequest;
use Kir\Http\Routing\Common\Stream;
use Kir\Http\Routing\Common\Uri;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route as SymfonyRoute;
use Throwable;

class Router {
	private RouteCollection $routes;

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
		$this->routes = new RouteCollection();
	}

	/**
	 * @param string $name
	 * @param string[] $methods
	 * @param string $pattern
	 * @param callable $callable
	 * @param callable|array<string, mixed>|object $params
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
	 * @param string $name
	 * @param string $pattern
	 * @param callable $callable
	 * @param callable|array<string, mixed>|object $params
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
	 * @param callable|array<string, mixed>|object $params
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
	 * @param callable|array<string, mixed>|object $params
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
	 * @param callable|array<string, mixed>|object $params
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

	/**
	 * @param ServerRequestInterface $request
	 * @return Route|null
	 */
	public function lookup(ServerRequestInterface $request): ?Route {
		$context = self::createRequestContextFromPsr($request);
		$matcher = new UrlMatcher($this->routes, $context);

		/** @var Throwable|array{_route: string, callable: callable} $route */
		$route = self::tryThis(static fn() => $matcher->match($request->getUri()->getPath()));
		if($route instanceof Throwable) {
			return null;
		}

		$originalRoute = $this->routes->get($route['_route']);
		if($originalRoute === null) {
			return null;
		}

		$params = array_diff_key($route, ['_route' => '', 'callable' => '']);

		/** @var mixed $parsedBody */
		$parsedBody = $request->getParsedBody();

		/** @var array<string, mixed> $postValues */
		$postValues = is_array($parsedBody) ? $parsedBody : [];

		/** @var array<string, mixed> $queryParams */
		$queryParams = $request->getQueryParams();

		/** @var array<string, mixed> $attributes */
		$attributes = $originalRoute->getOptions()['params'] ?? [];

		return new Route(
			name: $route['_route'],
			method: $request->getMethod(),
			queryParams: array_merge($queryParams, $params),
			postValues: $postValues,
			rawParsedBody: $parsedBody,
			callable: $route['callable'],
			attributes: $attributes
		);
	}

	private static function createRequestContextFromPsr(ServerRequestInterface $psrRequest): RequestContext {
		$uri = $psrRequest->getUri();

		$context = new RequestContext();

		$context->setMethod($psrRequest->getMethod());
		$context->setHost($uri->getHost());
		$context->setScheme($uri->getScheme());
		$context->setHttpPort($uri->getPort() ?: 80);
		$context->setHttpsPort($uri->getPort() ?: 443);

		// Path + Query
		$context->setPathInfo($uri->getPath());
		$context->setQueryString($uri->getQuery());

		// Base URL (optional, wenn du keinen Unterordner nutzt)
		// PSR requests haben keinen base path – falls nötig, musst du selbst entscheiden
		$context->setBaseUrl('');

		return $context;
	}

	/**
	 * @template T
	 * @param callable(): T $fn
	 * @return T|Throwable
	 */
	private static function tryThis($fn) {
		try {
			return $fn();
		} catch(Throwable $e) {
			return $e;
		}
	}
}
