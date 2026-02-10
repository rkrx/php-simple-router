<?php
namespace Kir\Http\Routing;

use Kir\Http\Routing\Common\Route;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Throwable;

class Router {
	private RouteCollection $routes;

	public function __construct(RouteCollection $routes) {
		$this->routes = $routes;
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
