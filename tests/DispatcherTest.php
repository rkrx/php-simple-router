<?php
namespace Kir\Http\Routing;

use PHPUnit_Framework_TestCase;
use ReflectionClass;

class DispatcherTest extends PHPUnit_Framework_TestCase {
	public function testDispatcher() {
		$dispatcher = new Dispatcher();
		$dispatcher->setClassFactory(function ($className) {
			$refClass = new ReflectionClass("Kir\\Http\\Routing\\Mock\\{$className}");
			return $refClass->newInstance();
		});
		$result = $dispatcher->invoke('TestClass', 'test', array('a' => 2, 'b' => 3));
		$this->assertEquals(5, $result);
	}
}