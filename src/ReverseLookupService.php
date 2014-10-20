<?php
namespace Kir\Http\Routing;

class ReverseLookupService {
	/**
	 * @var array
	 */
	private $lookup = array();

	/**
	 * @var array
	 */
	private $cache = array();

	/**
	 * @var Router
	 */
	private $router;

	/**
	 * @var callable
	 */
	private $fn;

	/**
	 * @param Router $router
	 */
	public function __construct(Router $router) {
		$this->router = $router;
		$this->fn = function ($data, $pattern) {
			if(!array_key_exists('alias', $data)) {
				throw new \Exception("Alias not found for route '{$pattern}'");
			}
			return $data['alias'];
		};
		$this->router->addNewRouteListener(function ($data, $params, $pattern) {
			$this->cache[$pattern] = $data;
			$alias = call_user_func($this->fn, $data, $pattern);
			$this->lookup[$alias] = $pattern;
		});
	}

	/**
	 * @param string $alias
	 * @throws \Exception
	 * @return string
	 */
	public function lookup($alias) {
		if(!array_key_exists($alias, $this->lookup)) {
			throw new \Exception("Alias not found: '{$alias}'");
		}
		return $this->lookup[$alias];
	}

	/**
	 * @param callable $fn
	 * @return $this
	 */
	public function setAliasHandler($fn) {
		$this->fn = $fn;
		return $this;
	}
}