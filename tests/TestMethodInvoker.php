<?php
namespace Kir\Http\Routing;

use Ioc\Exceptions\DefinitionNotFoundException;
use Ioc\Exceptions\Exceptions\ParameterMissingException;
use Ioc\MethodInvoker;

class TestMethodInvoker implements MethodInvoker {
	/**
	 * @param callable $callable
	 * @param array $arguments Must be an array were the keys match to the Variable-Names of the __construct'ors parameters.
	 * @throws DefinitionNotFoundException
	 * @throws ParameterMissingException
	 * @return mixed
	 */
	public function invoke($callable, array $arguments = array()) {
		return 'Test1234';
	}
}