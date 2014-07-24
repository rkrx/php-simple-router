<?php
namespace Kir\Http\Routing;

interface ServiceLocator {
	/**
	 * @param string $service
	 * @param object $caller
	 * @return object
	 */
	public function resolve($service, $caller);
}