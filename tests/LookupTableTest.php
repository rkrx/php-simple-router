<?php
namespace Kir\Http\Routing;

use Kir\Http\Routing\PatternConverter\DefaultPatternConverter;
use PHPUnit_Framework_TestCase;

class LookupTableTest extends PHPUnit_Framework_TestCase {
	public function testGet() {
		$router = new LookupTable(new DefaultPatternConverter());
		$router['/test'] = array('value' => 456);
		$router['/test[/:id]'] = array('value' => 123);
		$data = $router->lookup('/test/10');
		$this->assertEquals($data['data']['value'], 123);
		$this->assertEquals($data['params']['id'], 10);
	}
}