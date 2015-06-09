<?php
namespace Kir\Http\Routing;

class RouterTest extends \PHPUnit_Framework_TestCase {
	public function testLookup() {
		$router = new Router(new TestMethodInvoker());
		$router->get('/test/{id}', function ($id) {
			return $id;
		});
		$route = $router->lookup('/test/111');
		$this->assertArrayHasKey('methods', $route);
		$this->assertArrayHasKey('GET', $route['methods']);
		$this->assertArrayHasKey('callback', $route['methods']['GET']);
		$this->assertArrayHasKey('params', $route['methods']['GET']);
		$this->assertEquals('/test/{id}', $route['route']);
		$this->assertEquals('111', $route['params']['id']);

	}

	public function testGetResponse() {
		$router = new Router(new TestMethodInvoker());
		$router->get('/test/{id}', function ($id) {
			return $id;
		});
		$response = $router->getResponse('GET', '/test/111');
		$this->assertEquals('Test1234', $response);
	}

	public function testDispatch() {
		$router = new Router(new TestMethodInvoker());
		$router->get('/test/{id}', function ($id) {
			return $id;
		});
		ob_start();
		$router->dispatch('GET', '/test/111');
		$content = ob_get_clean();
		$this->assertEquals('Test1234', $content);
	}
}
