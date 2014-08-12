<?php
namespace Kir\Http\Routing;

use PHPUnit_Framework_TestCase;

class RouterTest extends PHPUnit_Framework_TestCase {
	public function testGet() {
		$routes = array(
			'GET /test/:id' => array('value' => 123),
			'GET /test' => array('value' => 456),
		);
		$router = new Router($routes);
		$data = $router->lookup('/test/10', 'GET');
		$this->assertEquals($data['data']['value'], 123);
		$this->assertEquals($data['params']['id'], 10);
	}

	public function testPost() {
		$routes = array(
			'POST /test/:id' => array('value' => 123),
			'POST /test' => array('value' => 456),
		);
		$router = new Router($routes);
		$data = $router->lookup('/test/10', 'POST');
		$this->assertEquals($data['data']['value'], 123);
		$this->assertEquals($data['params']['id'], 10);
	}

	public function testOptionalParameters() {
		$routes = array(
			'GET /test[/:id][/:start]' => array('value' => 123),
		);
		$router = new Router($routes);
		$data = $router->lookup('/test/123/10', 'GET');
		$this->assertArrayHasKey('params', $data);
		$params = $data['params'];
		$this->assertArrayHasKey('id', $params);
		$this->assertEquals($params['id'], 123);
		$this->assertArrayHasKey('start', $params);
		$this->assertEquals($params['start'], 10);

		$routes = array(
			'GET /test[/:id][/:start]' => array('value' => 123),
		);
		$router = new Router($routes);
		$data = $router->lookup('/test', 'GET');
		$this->assertArrayHasKey('params', $data);
		$params = $data['params'];
		$this->assertArrayNotHasKey('id', $params);
		$this->assertArrayNotHasKey('start', $params);

		$routes = array(
			'GET /test[/:id][/:start]' => array('value' => 123),
		);
		$router = new Router($routes);
		$data = $router->lookup('/test/10', 'GET');
		$this->assertArrayHasKey('params', $data);
		$params = $data['params'];
		$this->assertArrayHasKey('id', $params);
		$this->assertEquals($params['id'], 10);
		$this->assertArrayNotHasKey('start', $params);

		$routes = array(
			'GET /test[/:id/:start]' => array('value' => 123),
		);
		$router = new Router($routes);
		$data = $router->lookup('/test/10/20', 'GET');
		$this->assertArrayHasKey('params', $data);
		$params = $data['params'];
		$this->assertArrayHasKey('id', $params);
		$this->assertArrayHasKey('start', $params);
		$this->assertEquals($params['id'], 10);
		$this->assertEquals($params['start'], 20);
	}
}