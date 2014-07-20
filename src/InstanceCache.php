<?php
namespace Kir\Http\Routing;

interface InstanceCache {
	/**
	 * @param string $className
	 * @return bool
	 */
	public function has($className);

	/**
	 * @param string $className
	 * @return object
	 */
	public function get($className);

	/**
	 * @param string $className
	 * @param object $instance
	 * @return $this
	 */
	public function set($className, $instance);
}