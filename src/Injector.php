<?php
namespace Kir\Http\Routing;

use ReflectionClass;
use arr;

class Injector {
	/**
	 * @var ServiceLocator
	 */
	private $sl;

	/**
	 * @var array
	 */
	private $values;

	/**
	 * @param ServiceLocator $sl
	 */
	public function __construct(ServiceLocator $sl) {
		$this->sl = $sl;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return $this
	 */
	public function register($name, $value) {
		$this->values[$name] = $value;
		return $this;
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
				if(arr\has($this->values, $paramName)) {
					$param = $this->values[$paramName];
				} else {
					$param = $this->sl->resolve($paramName, $this);
				}
				$params[] = $param;
			}
			$instance = $ref->newInstanceArgs($params);
			return $instance;
		}
		return $ref->newInstance();
	}
}