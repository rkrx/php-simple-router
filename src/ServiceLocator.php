<?php
namespace Kir\Http\Routing;

interface ServiceLocator {
	/**
	 * @param string $service
	 * @return object
	 */
	public function resolve($service);
}