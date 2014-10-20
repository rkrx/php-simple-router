<?php
namespace Kir\Http\Routing;

class RouteBuilderTest extends \PHPUnit_Framework_TestCase {
	public function testBuild() {
		$builder = new RouteBuilder();
		$route = $builder->buildRoute('GET /', []);
		$this->assertEquals('/', $route);

		$route = $builder->buildRoute('GET /user/{id}', ['id' => 123]);
		$this->assertEquals('/user/123', $route);
	}
}
