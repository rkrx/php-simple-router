<?php
namespace Kir\Http\Routing\PatternConverter;

class DefaultPatternConverterTest extends \PHPUnit_Framework_TestCase {
	public function testSimple() {
		$converter = new DefaultPatternConverter();
		$pattern = $converter->convert('GET /test');
		$this->assertEquals('GET \/test', $pattern);
	}

	public function testComplex() {
		$converter = new DefaultPatternConverter();
		$pattern = $converter->convert('GET /test[/:id[/:offset]]');
		$this->assertEquals('GET \/test(?:\/(?P<id>.*?)(?:\/(?P<offset>.*?))?)?', $pattern);
	}

	public function testEscapeA() {
		$converter = new DefaultPatternConverter();
		$pattern = $converter->convert('GET /test\\[aa\\]');
		$this->assertEquals('GET \/test\\\\[aa\\\\]', $pattern);
	}
}
