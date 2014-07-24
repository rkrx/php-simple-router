<?php
namespace Kir\Http\Routing;

use Kir\Http\Routing\ServiceLocators\ClosureServiceLocator;
use PHPUnit_Framework_TestCase;
use ReflectionClass;

class InjectorTest extends PHPUnit_Framework_TestCase {
	public function testInjector() {
		$simpleServiceLocator = new ClosureServiceLocator(function ($serviceName, Injector $injector) {
			return $injector->createInstance("Kir\\Http\\Routing\\Mock\\{$serviceName}");
		});
		$injector = new Injector($simpleServiceLocator);
		$instance = $injector->createInstance('Kir\\Http\\Routing\\Mock\\TestClass2');
		$this->assertInstanceOf('Kir\\Http\\Routing\\Mock\\TestClass', $instance->testClass);
	}
}