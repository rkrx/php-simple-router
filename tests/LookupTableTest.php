<?php
namespace Kir\Http\Routing;

use PHPUnit_Framework_TestCase;

class LookupTableTest extends PHPUnit_Framework_TestCase {
	public function testGet() {
		$routes = array(
			'GET /test[/:id]' => array('value' => 123),
			'GET /test' => array('value' => 456),
		);
		$router = new LookupTable($routes);
		$data = $router->lookup('GET /test/10');
		$this->assertEquals($data['data']['value'], 123);
		$this->assertEquals($data['params']['id'], 10);
	}
}