<?php

namespace Kir\Http\Routing\ResponseTypes;

use IteratorAggregate;
use Override;
use Traversable;

/**
 * @implements IteratorAggregate<int, mixed[]>
 * @phpstan-type SettingsType array{separator?: string, enclosedBy?: string}
 */
class CSVDownloadGeneratorResponse extends AbstractHttpResponse implements IteratorAggregate {
	/** @var callable */
	private $fn;

	/**
	 * @param callable $fn
	 * @param SettingsType $settings
	 * @param int $statusCode
	 */
	public function __construct(callable $fn, private readonly array $settings, int $statusCode = 200) {
		parent::__construct($statusCode);
		$this->fn = $fn;
	}

	/**
	 * @return SettingsType
	 */
	public function getSettings(): array {
		return $this->settings;
	}

	/**
	 * @inheritDoc
	 * @return Traversable<mixed[]>
	 */
	public function getIterator(): Traversable {
		/** @var Traversable<mixed[]> $result */
		$result = call_user_func($this->fn);
		yield from $result;
	}

	/**
	 * @return array<string, mixed>
	 */
	#[Override]
    public function jsonSerialize(): mixed {
		$data = parent::jsonSerialize();
		$data['settings'] = $this->settings;
		return $data;
	}
}
