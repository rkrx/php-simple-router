<?php
namespace Kir\Http\Routing\ServiceLocators;

use Closure;
use Kir\Http\Routing\ServiceLocator;

class SimpleServiceLocator implements ServiceLocator {
	/**
	 * @var array
	 */
	private $services;

	/**
	 * @var object[]
	 */
	private $instances = array();

	/**
	 * @param string $service
	 * @param Closure $callable
	 * @return $this
	 */
	public function define($service, Closure $callable) {
		$this->services[$service] = $callable;
		return $this;
	}

	/**
	 * @param string $service
	 * @return object
	 */
	public function resolve($service) {
		if(!array_key_exists($service, $this->instances)) {
			$this->instances[$service] = $this->services[$service]();
		}
		return $this->instances[$service];
	}
}