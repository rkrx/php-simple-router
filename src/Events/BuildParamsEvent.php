<?php

namespace DvTeam\Routing\Events;

class BuildParamsEvent {
	/**
	 * @param array<int|string, mixed> $queryParams
	 */
	public function __construct(
		public array $queryParams
	) {}
}