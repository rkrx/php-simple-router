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

	/**
	 * @return array<string, mixed>
	 */
	public function allParams(): array {
		$allParams = array_merge($this->postValues ?? [], $this->queryParams);

		if(is_array($this->rawParsedBody)) {
			$keys = self::getOnlyStringKeysInParsedBodyParams($this->rawParsedBody);
			$allParams = array_merge($allParams, $keys);
		}

		/** @var array<string, mixed> $allParams */
		$allParams['httpData'] = [
			'httpData' => $allParams['httpData'] ?? null, // Safe what's in here
			'queryParams' => $this->queryParams,
			'postVars' => $this->postValues,
			'rawPostBody' => $this->rawParsedBody,
		];

		return $allParams;
	}

	/**
	 * @param mixed $parsedBody
	 * @return array<string, mixed>
	 */
	private static function getOnlyStringKeysInParsedBodyParams(mixed $parsedBody): array {
		if(!is_array($parsedBody)) {
			return [];
		}
		$result = [];
		foreach($parsedBody as $key => $value) {
			if(!is_numeric($key)) {
				$parsedBodyParams[$key] = $value;
			}
		}
		return $result;
	}
}
