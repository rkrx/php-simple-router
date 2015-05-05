<?php
namespace Kir\Http\Routing\PatternConverter;

class DefaultPatternConverter implements PatternConverter {
	/**
	 * @param string $pattern
	 * @return string
	 */
	public function convert($pattern) {
		printf("%s\n", $pattern);
		$parts = preg_split('/(?<![\\x5c])(?:[\\x5c]{2})*(\\[|\\]|:\\w+)/', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
		print_r($parts);
		list($parts) = $this->buildParts($parts);
		$pattern = join('', $parts);
		return $pattern;
	}

	/**
	 * @param array $parts
	 * @param int $offset
	 * @return array
	 */
	public function buildParts(array $parts, $offset = 0) {
		for($idx = $offset; $idx < count($parts); $idx++) {
			if($parts[$idx] === '[') {
				$parts[$idx] = '(?:';
				list($parts, $idx) = $this->buildParts($parts, $idx + 1);
			} elseif($parts[$idx] === ']') {
				$parts[$idx] = ')?';
				return array($parts, $idx);
			} elseif($parts[$idx][0] === ':') {
				$parts[$idx] = sprintf('(?P<%s>.*?)', substr($parts[$idx], 1));
			} else {
				$parts[$idx] = preg_quote($parts[$idx], '/');
			}
		}
		return array($parts, $idx);
	}
}