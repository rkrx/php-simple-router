<?php

namespace Kir\Http\Routing\Common;

class Route {
	/**
	 * @param string $name
	 * @param array<string, mixed> $params
	 * @param callable $handler
	 */
	public function __construct(
		public string $name,
		public string $method,
		public array $params,
		public $handler
	) {}
}
