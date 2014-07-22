<?php
namespace Kir\Http\Routing;

use Exception;
use ReflectionClass;
use ReflectionObject;

class Dispatcher {
	/**
	 * @var ServiceLocator
	 */
	private $serviceLocator = null;

	/**
	 * @var InstanceCache
	 */
	private $instanceCache;

	/**
	 * @param ServiceLocator $serviceLocator
	 * @param InstanceCache $instanceCache
	 */
	public function __construct(ServiceLocator $serviceLocator, InstanceCache $instanceCache) {
		$this->serviceLocator = $serviceLocator;
		$this->instanceCache = $instanceCache;
	}

	/**
	 * @param string $className
	 * @param string $method
	 * @param array $params
	 * @return mixed
	 */
	public function invoke($className, $method, array $params) {
		if($this->instanceCache->has($className)) {
		}
		$inst = $this->getInstance($className);
		return $this->invokeMethod($method, $inst, $params);
	}

	/**
	 * @param string $className
	 * @return object
	 */
	public function getInstance($className) {
		$ref = new ReflectionClass($className);
		if($ref->hasMethod('__construct')) {
			$constructor = $ref->getMethod('__construct');
			$params = array();
			foreach($constructor->getParameters() as $parameter) {
				$paramName = $parameter->getName();
				$param = $this->serviceLocator->resolve($paramName);
				$params[] = $param;
			}
			$instance = $ref->newInstanceArgs($params);
			return $instance;
		}
		return $ref->newInstance();
	}

	/**
	 * @param string $method
	 * @param object $inst
	 * @param array $params
	 * @throws Exception
	 * @return mixed
	 */
	private function invokeMethod($method, $inst, $params) {
		$refObject = new ReflectionObject($inst);
		if(!$refObject->hasMethod($method)) {
			throw new Exception("Missing method {$method}");
		}
		$refMethod = $refObject->getMethod($method);
		$parameters = array();
		foreach($refMethod->getParameters() as $parameter) {
			if(array_key_exists($parameter->getName(), $params)) {
				$value = $params[$parameter->getName()];
			} else {
				$value = null;
			}
			$parameters[] = $value;
		}
		return $refMethod->invokeArgs($inst, $parameters);
	}
}