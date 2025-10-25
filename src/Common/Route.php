<?php

namespace Kir\Http\Routing\Common;

class Route {
	/**
	 * @param string $name
	 * @param array<string, mixed> $attributes
	 * @param callable|array<string, mixed>|object $params
	 */
	public function __construct(
		public string $name,
		public string $method,
		public array $attributes,
		public $params
	) {}
}
