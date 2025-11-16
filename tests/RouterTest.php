<?php
namespace Kir\Http\Routing;

use Kir\Http\Routing\Common\Route;
use Kir\Http\Routing\Common\ServerRequest;
use Kir\Http\Routing\Common\TestMethodInvoker;
use Kir\Http\Routing\Common\Uri;
use Kir\Http\Routing\ResponseTypes\HtmlResponse;
use PHPUnit\Framework\TestCase;
use Zend\Diactoros\Response;

class RouterTest extends TestCase {
	public function testLookup(): void {
		$uri = new Uri('http://test.localhost/test/123');
		$serverRequest = new ServerRequest(method: 'GET', uri: $uri, queryParams: [], parsedBody: []);

		$router = new Router();
		$router->get(name: 'some.name', pattern: '/test/{id}', callable: fn (int $id) => new HtmlResponse((string) $id), params: ['secure' => true]);
		$route = $router->lookup($serverRequest);

		self::assertInstanceOf(Route::class, $route);
		self::assertEquals('some.name', $route->name);
		self::assertEquals('GET', $route->method);
		self::assertEquals(['secure' => true], $route->attributes);
	}

	public function testFailingLookup(): void {
		$uri = new Uri('http://test.localhost/test/123');
		$serverRequest = new ServerRequest(method: 'GET', uri: $uri, queryParams: [], parsedBody: []);

		$router = new Router();
		$router->get(name: 'some.name', pattern: '/non-existing/{id}', callable: fn (int $id) => new HtmlResponse((string) $id), params: ['secure' => true]);
		$route = $router->lookup($serverRequest);

		self::assertNull($route);
	}

	public function testDispatch(): void {
		$uri = new Uri('http://test.localhost/test/123');
		$serverRequest = new ServerRequest(method: 'GET', uri: $uri, queryParams: [], parsedBody: []);

		$router = new RouteHandler(new TestMethodInvoker());
		$router->getRouter()->get(name: 'some.name', pattern: '/test/{id}', callable: fn (int $id) => new HtmlResponse((string) $id), params: ['secure' => true]);

		$result = $router->dispatch(request: $serverRequest, response: new Response());

		self::assertInstanceOf(Response::class, $result);
		self::assertEquals(['text/html; charset=utf-8'], $result->getHeader('Content-Type'));
		self::assertEquals('123', $result->getBody()->getContents());
	}
}
