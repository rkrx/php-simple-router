<?php
namespace Kir\Http\Routing;

use ReflectionClass;

class Injector {
	/**
	 * @var ServiceLocator
	 */
	private $sl;

	/**
	 * @param ServiceLocator $sl
	 */
	public function __construct(ServiceLocator $sl) {
		$this->sl = $sl;
	}

	/**
	 * @param string $className
	 * @return object
	 */
	public function createInstance($className) {
		$ref = new ReflectionClass($className);
		if($ref->hasMethod('__construct')) {
			$constructor = $ref->getMethod('__construct');
			$params = array();
			foreach($constructor->getParameters() as $parameter) {
				$paramName = $parameter->getName();
				$param = $this->sl->resolve($paramName);
				$params[] = $param;
			}
			$instance = $ref->newInstanceArgs($params);
			return $instance;
		}
		return $ref->newInstance();
	}
}