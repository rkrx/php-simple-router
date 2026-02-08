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
use Kir\Http\Routing\RouterBuilder;

$routerBuilder = new RouterBuilder();
$routerBuilder->get(
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

$router = $routerBuilder->build();
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
$routerBuilder->add(
    name: 'article.show',
    methods: ['GET', 'HEAD'],
    pattern: '/articles/{id}',
    callable: fn (string $id) => "Article {$id}",
    params: ['cache' => true]
);

$routerBuilder->post(
    name: 'session.create',
    pattern: '/login',
    callable: fn () => 'Logged in',
    params: []
);
```

### Lookup and params

```php
$router = $routerBuilder->build();
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

### RouterBuilder

- **Purpose:** register routes and build a `Router`.
- **Stability:** not explicitly stated (see project-level stability).
- **Constructor:** `__construct()` creates an empty route collection.
- **Methods:**
  - `add(string $name, array $methods, string $pattern, callable $callable, callable|array|object $params): self` registers a route and returns the router for chaining.
  - `get/post/put/delete(...)` are convenience wrappers around `add()`.
  - `addDefinitions(array $definitions): self` registers routes from a config array.
  - `build(): Router` creates a router instance for lookups.
  - `createServerRequestFromEnv(?Uri $uri = null): ServerRequest` builds a request from globals; throws `RuntimeException` on invalid JSON input.

### Router

- **Purpose:** match a PSR-7 request to a `Route`.
- **Stability:** not explicitly stated (see project-level stability).
- **Constructor:** built via `RouterBuilder::build()`.
- **Methods:**
  - `lookup(ServerRequestInterface $request): ?Route` returns a matched route or `null` (no exception on missing routes).
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

## OpenAPI integration

This project can generate a route configuration from OpenAPI PHP attributes (via `zircote/swagger-php`).
The generator scans your PHP code for OpenAPI HTTP method attributes (e.g. `#[OA\Get]`, `#[OA\Post]`)
and writes a route config file that can be loaded into `RouterBuilder`.

### Generate route config from OpenAPI attributes

```sh
php bin/generate-openapi-routes.php \
  --index-path var/openapi-index.xml \
  --route-config-path var/openapi-routes.php \
  src tests
```

- `src`/`tests` are the scan paths (you can pass multiple).
- `--index-path` and `--route-config-path` are required unless you set
  `OPENAPI_INDEX_PATH` and/or `OPENAPI_ROUTE_CONFIG_PATH`.
- The output file is a PHP array with `routes` entries you can load as definitions.

### Load the generated routes

```php
/** @var array{routes?: array<int, array<string, mixed>>} $definitions */
$definitions = require __DIR__ . '/var/openapi-routes.php';

$routerBuilder = new RouterBuilder();
$routerBuilder->addDefinitions($definitions);
$router = $routerBuilder->build();
```

### OpenAPI metadata in routes

OpenAPI-specific metadata (e.g. `security`) is stored under
`$route->attributes['openapi']`. This lets you enforce auth or other policies
at dispatch time without mixing them into your normal route params.

### Required OpenAPI info

`swagger-php` requires an `Info` definition in the scanned code, e.g.:

```php
use OpenApi\Attributes as OA;

#[OA\Info(title: 'Example API', version: '1.0.0')]
final class OpenApiSpec {}
```

## Error handling

- `Router::lookup()` returns `null` when no route matches or the matcher fails.
- `RouterBuilder::createServerRequestFromEnv()` throws `RuntimeException` if JSON input cannot be read or decoded.
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
