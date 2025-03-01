<?php
namespace Kir\Http\Routing\Common;

use Ioc\Exceptions\DefinitionNotFoundException;
use Ioc\MethodInvoker;
use ReflectionException;
use ReflectionFunction;

class TestMethodInvoker implements MethodInvoker {
	/**
	 * @param callable $callable
	 * @param array<string, mixed> $arguments Must be an array were the keys match to the Variable-Names of the __construct'ors parameters.
	 * @throws DefinitionNotFoundException
	 * @return mixed
	 */
	public function invoke($callable, array $arguments = []) {
		$params = self::assocParams($callable, $arguments);
		return $callable(...$params);
	}

	/**
	 * @param callable $callback
	 * @param array<string, mixed> $arguments
	 * @return mixed[]
	 * @throws ReflectionException
	 */
	private static function assocParams($callback, array $arguments): array {
		// @phpstan-ignore-next-line
		$reflection = new ReflectionFunction($callback);

		$result = [];
		foreach ($reflection->getParameters() as $parameter) {
			if(!array_key_exists($parameter->name, $arguments)) {
				break;
			}
			$result[] = $arguments[$parameter->name];
		}
		return $result;
	}
}
