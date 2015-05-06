<?php
namespace Kir\Http\Routing\PatternConverter;

class DefaultPatternConverterTest extends \PHPUnit_Framework_TestCase {
	public function testSimple() {
		$converter = new DefaultPatternConverter();
		$pattern = $converter->convert('/test');
		$this->assertEquals('\/test', $pattern);
	}

	public function testComplex() {
		$converter = new DefaultPatternConverter();
		$pattern = $converter->convert('/test[/:id[/:offset]]');
		$this->assertEquals('\/test(?:\/(?P<id>.*?)(?:\/(?P<offset>.*?))?)?', $pattern);
	}

	public function testEscapeA() {
		$converter = new DefaultPatternConverter();
		$pattern = $converter->convert('/test\\[aa\\]');
		$this->assertEquals('\/test\\[aa\\]', $pattern);
	}

	public function testEscapeB() {
		$converter = new DefaultPatternConverter();
		$pattern = $converter->convert('/test\\\\\\[aa\\\\\\]');
		$this->assertEquals('\/test\\\\\\[aa\\\\\\]', $pattern);
	}
}
