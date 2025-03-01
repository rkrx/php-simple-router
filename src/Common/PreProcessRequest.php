<?php

namespace Kir\Http\Routing\Common;

class PreProcessRequest {
	/**
	 * @param mixed $handler
	 * @param bool $stopPropagation
	 */
	public function __construct(
		public readonly mixed $handler,
		public bool $stopPropagation = false
	) {}
}
