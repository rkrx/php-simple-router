<?php
namespace Kir\Http\Routing;

class ReverseLookupServiceTest extends \PHPUnit_Framework_TestCase {
	public function testLookup() {
		$router = new LookupTable();
		$router['GET /'] = ['alias' => 'start'];

		$reverseLookup = new ReverseLookupService($router);
		$pattern = $reverseLookup->lookup('start');

		$this->assertEquals('GET /', $pattern);
	}
}
