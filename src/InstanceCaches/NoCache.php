<?php
namespace Kir\Http\Routing\InstanceCaches;

use Kir\Http\Routing\InstanceCache;

class NoCache implements InstanceCache {
	/**
	 * @param string $className
	 * @return bool
	 */
	public function has($className) {
		return false;
	}

	/**
	 * @param string $className
	 * @return object
	 */
	public function get($className) {
		return null;
	}

	/**
	 * @param string $className
	 * @param object $instance
	 * @return $this
	 */
	public function set($className, $instance) {
		return $this;
	}
}