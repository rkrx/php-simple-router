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
		$this->assertEquals($data['value'], 123);
		$this->assertEquals($data['params']['id'], 10);
	}

	public function testPost() {
		$routes = array(
			'POST /test/:id' => array('value' => 123),
			'POST /test' => array('value' => 456),
		);
		$router = new Router($routes);
		$data = $router->lookup('/test/10', 'POST');
		$this->assertEquals($data['value'], 123);
		$this->assertEquals($data['params']['id'], 10);
	}
}