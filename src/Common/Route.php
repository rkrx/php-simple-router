<?php

namespace Kir\Http\Routing\Common;

class Route {
	/**
	 * @param string $name
	 * @param string $method
	 * @param array<string, mixed> $queryParams
	 * @param array<string, mixed>|null $postValues
	 * @param mixed $rawParsedBody
	 * @param callable $callable
	 * @param callable|array<string, mixed>|object $attributes
	 */
	public function __construct(
		public string $name,
		public string $method,
		public array $queryParams,
		public ?array $postValues,
		public mixed $rawParsedBody,
		public $callable,
		public $attributes
	) {}
}
