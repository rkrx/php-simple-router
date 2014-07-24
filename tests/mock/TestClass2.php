<?php
namespace Kir\Http\Routing\Mock;

class TestClass2 {
	/**
	 * @var TestClass
	 */
	public $testClass;

	/**
	 * @param TestClass $testClass
	 */
	public function __construct(TestClass $testClass) {
		$this->testClass = $testClass;
	}

	/**
	 * @param mixed $a
	 * @param mixed $b
	 * @return mixed
	 */
	public function test($a, $b) {
		return $a + $b;
	}
}