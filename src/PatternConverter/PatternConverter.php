<?php
namespace Kir\Http\Routing\PatternConverter;

interface PatternConverter {
	/**
	 * @param string $pattern
	 * @return string
	 */
	public function convert($pattern);
}