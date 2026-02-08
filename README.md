# php-simple-router

A small, framework-agnostic router built on Symfony Routing that matches PSR-7 requests and provides optional dispatch helpers.

## Status

- PHP: >= 8.1
- Extensions: ext-json (used for JSON body parsing)
- PSR: psr/http-message >= 1.0 (PSR-7 interfaces)
- Core dependencies: symfony/routing ^7.3, symfony/http-foundation ^7.3
- Frameworks: framework-agnostic (Symfony Routing used internally)
- Stability: not explicitly stated; pin versions in production
- Style: minimal, unopinionated routing + optional dispatch layer

## Installation

```sh
composer require rkr/simple-router
```

## Quick start

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Kir\Http\Routing\Common\ServerRequest;
use Kir\Http\Routing\Common\Uri;
use Kir\Http\Routing\Router;

$router = new Router();
$router->get(
    name: 'hello',
    pattern: '/hello/{name}',
    callable: fn (string $name) => "Hello {$name}",
    params: ['secure' => true]
);

$request = new ServerRequest(
    method: 'GET',
    uri: new Uri('http://example.test/hello/Ada'),
    queryParams: [],
    parsedBody: []
);

$route = $router->lookup($request);
if ($route === null) {
    http_response_code(404);
    exit('Not found');
}

$params = $route->allParams();
$result = ($route->callable)($params['name']);

echo $result;
```

## Usage

### Register routes

```php
$router->add(
    name: 'article.show',
    methods: ['GET', 'HEAD'],
    pattern: '/articles/{id}',
    callable: fn (string $id) => "Article {$id}",
    params: ['cache' => true]
);

$router->post(
    name: 'session.create',
    pattern: '/login',
    callable: fn () => 'Logged in',
    params: []
);
```

### Lookup and params

```php
$route = $router->lookup($request);

if ($route !== null) {
    $allParams = $route->allParams();

    // Route params and query params are merged into $allParams.
    // Raw body data is available under $allParams['httpData'].
}
```

### Dispatch with RouteHandler

```php
use Ioc\MethodInvoker;
use Kir\Http\Routing\RouteHandler;
use Kir\Http\Routing\Router;
use Kir\Http\Routing\Common\ServerRequest;
use Kir\Http\Routing\Common\Uri;
use Kir\Http\Routing\ResponseTypes\HtmlResponse;

$methodInvoker = /* your MethodInvoker implementation */;
$handler = new RouteHandler($methodInvoker);

$handler->getRouter()->get(
    name: 'home',
    pattern: '/',
    callable: fn () => new HtmlResponse('<h1>Hi</h1>'),
    params: []
);

$request = new ServerRequest(
    method: 'GET',
    uri: new Uri('http://example.test/'),
    queryParams: [],
    parsedBody: []
);

$response = $handler->dispatch($request, Router::createResponse());
```

## Public API overview

### Router

- **Purpose:** register routes and match a PSR-7 request to a `Route`.
- **Stability:** not explicitly stated (see project-level stability).
- **Constructor:** `__construct()` creates an empty route collection.
- **Methods:**
  - `add(string $name, array $methods, string $pattern, callable $callable, callable|array|object $params): self` registers a route and returns the router for chaining.
  - `get/post/put/delete(...)` are convenience wrappers around `add()`.
  - `lookup(ServerRequestInterface $request): ?Route` returns a matched route or `null` (no exception on missing routes).
  - `createServerRequestFromEnv(?Uri $uri = null): ServerRequest` builds a request from globals; throws `RuntimeException` on invalid JSON input.
  - `createResponse(): Response` returns a basic PSR-7 response with an empty `Stream` body.

### RouteHandler

- **Purpose:** dispatch a matched route using a `MethodInvoker`, and translate `AbstractHttpResponse` results into PSR-7 responses.
- **Stability:** not explicitly stated (see project-level stability).
- **Constructor:** `__construct(MethodInvoker $methodInvoker)` registers default post-processors for built-in response types.
- **Methods:**
  - `dispatch(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface` runs the route callable and returns a PSR-7 response; throws routing or post-processing exceptions (see Error handling).
  - `addPreProcessor(callable $fn): void` registers hooks executed before dispatch.
  - `setPostProcessor(string $className, Closure $handler): void` maps response types to PSR-7 responses.
  - `setErrorHandler(callable $errorHandler): void` customizes error handling.

### Route

- **Purpose:** the matched route with params and attributes.
- **Stability:** not explicitly stated (see project-level stability).
- **Constructor:** value object with `name`, `method`, `queryParams`, `postValues`, `rawParsedBody`, `callable`, `attributes`.
- **Methods:**
  - `allParams(): array` merges route params, query params, and parsed body and provides `httpData` metadata.

### Common PSR-7 helpers

- `Common\ServerRequest`, `Common\Response`, `Common\Uri`, `Common\Stream`: lightweight PSR-7 implementations used by the router (you can also pass any PSR-7 request/response types).

### Response types

Built-in `ResponseTypes` implement `AbstractHttpResponse` and are handled by the default `RouteHandler` post-processors:

- `HtmlResponse`, `JsonResponse`, `BinaryContentResponse`, `MimeTypeContentResponse`, `NotFoundResponse`
- Redirect helpers: `RedirectToResponse`, `RedirectToHttpsResponse`, `RedirectToRefererResponse`, `RedirectToSelfResponse`, `RedirectionResponse`
- `CSVDownloadGeneratorResponse`, `ViewResponse`

## Configuration

- No global configuration required.
- Route attributes are passed via the `$params` argument and exposed as `Route::$attributes`.
- Customize dispatch behavior via `RouteHandler` pre-processors, post-processors, and the error handler.

## Error handling

- `Router::lookup()` returns `null` when no route matches or the matcher fails.
- `Router::createServerRequestFromEnv()` throws `RuntimeException` if JSON input cannot be read or decoded.
- `RouteHandler::dispatch()` sets HTTP status codes and rethrows exceptions for:
  - Route not found (`RouteNotFoundException`, 400)
  - Method not registered (`MethodNotRegisteredException`, 400)
  - Invalid return type or missing post-processor (`InvalidReturnTypeException`, `NoPostProcessorDefinedForTypeException`)
  - Unknown errors (`UndefinedRouterException` or the original exception, 500)

## Testing

```sh
composer install
vendor/bin/phpunit -c tests.xml
composer phpstan
```

## Contributing and support

- Issues and pull requests are welcome via the repository's issue tracker.

## License

MIT. See `LICENSE`.
