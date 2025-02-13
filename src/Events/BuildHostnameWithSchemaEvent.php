<?php

namespace DvTeam\Routing\Events;

class BuildHostnameWithSchemaEvent {
	public function __construct(
		public string $hostname,
		public bool $isHttps,
		public string $result
	) {}
}