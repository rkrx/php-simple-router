<?php

namespace DvTeam\Routing\PreConditions;

/**
 * @phpstan-type Params array<int|string, mixed>
 */
class PreConditionEntity {
	/**
	 * @param string $className
	 * @param Params $params
	 */
	public function __construct(
		private readonly string $className,
		private readonly array $params
	) {}

	public function getClassName(): string {
		return $this->className;
	}
	
	/**
	 * @return Params
	 */
	public function getParams(): array {
		return $this->params;
	}
}
