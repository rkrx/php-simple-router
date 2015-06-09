<?php
namespace Kir\Http\Routing\Router;

use Kir\Data\Arrays\RecursiveAccessor\ArrayPath;

class PostData {
	/** @var ArrayPath\Map */
	private $postData = null;

	/**
	 * @param array $postData
	 */
	public function __construct(array $postData = null) {
		if($postData === null) {
			$postData = $_POST;
		}
		$this->postData = new ArrayPath\Map($postData);
	}

	/**
	 * @param array $path
	 * @return bool
	 */
	public function has(array $path) {
		return $this->postData->has($path);
	}

	/**
	 * @param array $path
	 * @param mixed $default
	 * @return mixed
	 */
	public function get(array $path, $default = null) {
		return $this->postData->get($path, $default);
	}
}