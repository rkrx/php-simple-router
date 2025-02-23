<?php

namespace DvTeam\Routing;

use DvTeam\Routing\Events\BuildHostnameWithSchemaEvent;
use DvTeam\Routing\Events\BuildParamsEvent;
use DvTeam\Routing\Exceptions\AliasNotFoundException;
use DvTeam\Routing\Exceptions\RoutingException;
use DvTeam\Routing\Exceptions\UrlPrefixNotFoundException;
use DvTeam\Routing\PreConditions\PreCondition;
use DvTeam\Routing\PreConditions\PreConditionEntity;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @phpstan-type RouteAlias array{
 *     ctrl: string,
 *     method: string,
 *     url-part: string,
 *     params: array<int|string, null|int|string>,
 *     https: bool
 * }
 *
 * @phpstan-type LinkToArgs array<string, mixed>|array{alias?: string}|array{ctrl: string, method: string}|array{"@target": array{class-string, string}}
 *
 * @phpstan-type RouteHttpParams array<int|string, null|int|string>
 *
 * @phpstan-type RouteUrlPart array{
 *     alias: string,
 *     ctrl: string,
 *     method: string,
 *     params?: array<int|string, null|int|string>,
 *     https: bool,
 *     preconditions: list<class-string<PreCondition>>
 * }
 * @phpstan-type RouteUrlParts array<string, RouteUrlPart>
 * @phpstan-type RouteUrlPartLookup array<string, RouteUrlParts>
 *
 * @phpstan-type RouteMod array{
 *     alias: string,
 *     url-part: string,
 *     params: RouteHttpParams,
 *     https: bool
 * }
 */
class Router {
	public const INSECURE = false;
	public const SECURE = true;
	
	/** @var array<string, RouteAlias> */
	private array $alias = [];
	/** @var RouteUrlPartLookup */
	private array $urlParts = [];
	/** @var array<string, RouteMod> */
	private array $mod = [];
	/** @var array<int, array<string, mixed>> */
	private array $stack = [];
	private string $defaultAlias;
	
	/**
	 * @param string $webRoot Should be initialized with something like '/'
	 * @param string $httpHost The http host of the environment. Example: example.org
	 * @param bool $isHttps Whether the incoming request is using https
	 */
	public function __construct(
		private readonly string $webRoot,
		private readonly string $httpHost,
		private readonly bool $isHttps,
		private readonly EventDispatcherInterface $dispatcher
	) {}
	
	public function getDefaultAlias(): string {
		return $this->defaultAlias;
	}
	
	public function setDefaultAlias(string $alias): Router {
		$this->defaultAlias = $alias;
		return $this;
	}
	
	/**
	 * @param string $alias
	 * @param string $urlPart
	 * @param array{class-string, string} $callable
	 * @param array<int|string, null|int|string> $params
	 * @param bool $https
	 * @param list<class-string<PreCondition>> $preconditions
	 * @return $this
	 */
	public function all(string $alias, string $urlPart, $callable, array $params = [], bool $https = true, array $preconditions = []) {
		return $this->addRoute($alias, $urlPart, $callable, ['GET', 'POST'], $params, $https, $preconditions);
	}
	
	/**
	 * @param string $alias
	 * @param string $urlPart
	 * @param array{class-string, string} $callable
	 * @param array<int|string, null|int|string> $params
	 * @param bool $https
	 * @param list<class-string<PreCondition>> $preconditions
	 * @return Router
	 */
	public function get(string $alias, string $urlPart, $callable, array $params = [], bool $https = true, array $preconditions = []) {
		return $this->addRoute($alias, $urlPart, $callable, ['GET'], $params, $https, $preconditions);
	}
	
	/**
	 * @param string $alias
	 * @param string $urlPart
	 * @param array{class-string, string} $callable
	 * @param array<int|string, null|int|string> $params
	 * @param bool $https
	 * @param list<class-string<PreCondition>> $preconditions
	 * @return Router
	 */
	public function post(string $alias, string $urlPart, $callable, array $params = [], bool $https = true, array $preconditions = []) {
		return $this->addRoute($alias, $urlPart, $callable, ['POST'], $params, $https, $preconditions);
	}
	
	/**
	 * @param string $alias
	 * @param string $urlPart
	 * @param array{class-string, string} $callable
	 * @param array<int, 'HEAD'|'GET'|'POST'|'PUT'|'DELETE'> $supportedHttpMethods
	 * @param array<int|string, null|int|string> $params
	 * @param bool $https
	 * @param list<class-string<PreCondition>> $preconditions
	 * @return $this
	 */
	public function addRoute(string $alias, string $urlPart, array $callable, array $supportedHttpMethods, array $params, bool $https = true, array $preconditions = []) {
		[$controller, $method] = $callable;
		$controller = ltrim((string) $controller, '\\');
		foreach($supportedHttpMethods as $supportedHttpMethod) {
			$this->urlParts[$urlPart][$supportedHttpMethod] = [
				'alias' => $alias,
				'ctrl' => $controller,
				'method' => $method,
				'params' => $params,
				'https' => $https,
				'preconditions' => $preconditions
			];
			
			$this->alias[$alias] = [
				'ctrl' => $controller,
				'method' => $method,
				'url-part' => $urlPart,
				'params' => $params,
				'https' => $https
			];
			
			$this->mod["{$controller}::{$method}"] = [
				'alias' => $alias,
				'url-part' => $urlPart,
				'params' => $params,
				'https' => $https
			];
		}
		
		return $this;
	}
	
	/**
	 * Creates a link without adding parameters from the context
	 *
	 * @param LinkToArgs $args
	 * @return string
	 */
	public function linkTo(array $args = []): string {
		if(count($this->stack) > 0) {
			$prev = $this->stack[count($this->stack)-1];
			foreach(['ctrl', 'method'] as $part) {
				if(!array_key_exists($part, $args) && array_key_exists($part, $prev)) {
					$args[$part] = $prev[$part];
				}
			}
		}
		
		if(array_key_exists('alias', $args)) { // Generate link from alias
			/** @var string $alias */
			$alias = $args['alias'];
			unset($args['alias']);
			if(array_key_exists($alias, $this->alias)) {
				$mod = $this->alias[$alias];
				$linkArgs = [$mod['url-part']];
				unset($args['ctrl'], $args['method']);
				foreach($mod['params'] as $paramName => $defaultValue) {
					if(is_numeric($paramName)) {
						if($defaultValue === null) {
							continue;
						}
						$paramName = $defaultValue;
						$defaultValue = null;
					}
					if(array_key_exists($paramName, $args)) {
						/** @var string $urlPart */
						$urlPart = $args[$paramName];
						$linkArgs[] = $urlPart;
						unset($args[$paramName]);
					} else {
						/** @var string $urlPart */
						$urlPart = $defaultValue;
						$linkArgs[] = $urlPart;
					}
				}
				$requestUri = $this->buildParams($linkArgs, $args);
				return sprintf('%s%s/%s', $this->buildHostname($mod['https']), rtrim($this->webRoot, '/'), ltrim($requestUri, '/'));
			}
			throw new AliasNotFoundException("Alias not found: {$alias}");
		}
		
		if(array_key_exists('@target', $args)) { // Generate link from @target
			/** @var array{class-string, string} $target */
			$target = $args['@target'];
			[$args['ctrl'], $args['method']] = $target;
			unset($args['@target']);
		}
		
		if(array_key_exists('ctrl', $args) && array_key_exists('method', $args)) { // Generate link from ctrl/method
			/** @var array{ctrl: string, method: string} $parts */
			$parts = $args;
			$key = "{$parts['ctrl']}::{$parts['method']}";
			unset($args['ctrl'], $args['method']);
			if(array_key_exists($key, $this->mod)) {
				$mod = $this->mod[$key];
				$linkArgs = [$mod['url-part']];
				foreach($mod['params'] as $paramName => $defaultValue) {
					if(is_numeric($paramName)) {
						$paramName = $defaultValue;
						$defaultValue = null;
					}
					if(array_key_exists((string) $paramName, $args)) {
						$urlPart = ((string) $args[$paramName]) ?: '_';
						$linkArgs[] = $urlPart;
						unset($args[$paramName]);
					} else {
						$urlPart = ((string) $defaultValue) ?: '_';
						$linkArgs[] = $urlPart;
					}
				}
				$requestUri = $this->buildParams($linkArgs, $args);
				return sprintf('%s%s/%s', $this->buildHostname($mod['https']), rtrim($this->webRoot, '/'), ltrim($requestUri, '/'));
			}
			
			throw new RoutingException("Ctrl/Method-Pair not found: {$key}");
		}
		
		throw new RoutingException('Alias or Ctrl/Method not provided');
	}
	
	/**
	 * Creates a link to itself. Also adding parameters from the context.
	 *
	 * @param array<string, mixed> $args
	 * @return string
	 */
	public function linkToSelf(array $args = []): string {
		if(count($this->stack) > 0) {
			$prev = $this->stack[count($this->stack)-1];
			if(!array_key_exists('alias', $args) && !(array_key_exists('ctrl', $args) || array_key_exists('method', $args))) {
				$args['alias'] = $prev['alias'];
				unset($prev['ctrl'], $prev['method']);
			} elseif(array_key_exists('ctrl', $args) || array_key_exists('method', $args)) {
				if(!array_key_exists('ctrl', $args) && array_key_exists('ctrl', $prev)) {
					$args['ctrl'] = $prev['ctrl'];
				}
				if(!array_key_exists('method', $args) && array_key_exists('method', $prev)) {
					$args['method'] = $prev['method'];
				}
				unset($prev['alias'], $prev['ctrl'], $prev['method']);
				$args = array_merge($prev, $args);
			}
			$args = array_merge($prev, $args);
		} elseif(!array_key_exists('alias', $args)) {
			throw new RoutingException('Insufficient information to build url: No alias given and no alias found on stack');
		}
		return $this->linkTo(array_merge($args));
	}
	
	/**
	 * @template T
	 * @param array<string, mixed>|array{alias?: string, ctrl?: string, method?: string} $arguments
	 * @param callable(): T $inner
	 * @return T
	 */
	public function enterContext(array $arguments, callable $inner) {
		// Expand missing arguments
		if(array_key_exists('alias', $arguments)) {
			$data = $this->alias[$arguments['alias']];
			if(!array_key_exists('ctrl', $arguments)) {
				$arguments['ctrl'] = $data['ctrl'];
			}
			if(!array_key_exists('method', $arguments)) {
				$arguments['method'] = $data['method'];
			}
		} elseif(array_key_exists('ctrl', $arguments) && array_key_exists('method', $arguments)) {
			$data = $this->mod["{$arguments['ctrl']}::{$arguments['method']}"];
			$arguments['alias'] = $data['alias'];
		}
		try {
			$prev = [];
			if(count($this->stack) > 0) {
				$prev = $this->stack[count($this->stack) - 1];
			}
			$arguments = array_merge($prev, $arguments);
			$this->stack[] = $arguments;
			return $inner();
		} finally {
			array_pop($this->stack);
		}
	}
	
	/**
	 * @param string $httpMethod
	 * @param string $requestUri
	 * @return array{string, string, string, array<int|string, mixed>, list<class-string<PreCondition>|PreConditionEntity>}
	 */
	public function getCallParams(string $httpMethod, string $requestUri): array {
		$requestUri = trim($requestUri, '/');
		[$path, $query] = $this->parseUrl($requestUri);
		$parts = explode('/', $path ?? '');
		$urlPrefix = array_shift($parts);
		if($urlPrefix === '') {
			$urlPrefix = $this->defaultAlias;
		}
		if(array_key_exists($urlPrefix, $this->urlParts) && array_key_exists($httpMethod, $this->urlParts[$urlPrefix])) {
			$mod = $this->urlParts[$urlPrefix][$httpMethod];
		} else {
			throw new UrlPrefixNotFoundException("Undefined url prefix: {$urlPrefix}");
		}
		$args = [];
		$defaults = [];
		foreach($mod['params'] ?? [] as $paramName => $defaultValue) {
			if(is_numeric($paramName)) {
				$paramName = $defaultValue;
				$defaultValue = null;
			}
			$part = '_';
			if(count($parts)) {
				$part = array_shift($parts);
			}
			if($part === '_' || $part === '') {
				$defaults[$paramName] = $defaultValue;
			} elseif($part !== null) {
				$args[$paramName] = urldecode($part);
			}
		}
		return [$mod['alias'], $mod['ctrl'], $mod['method'], array_merge($defaults, $query, $args), $mod['preconditions']];
	}
	
	/**
	 * @param list<null|string> $pathParts
	 * @param array<int|string, mixed> $queryParams
	 * @return string
	 */
	private function buildParams(array $pathParts, array $queryParams): string {
		$path = [];
		foreach($pathParts as $pathPart) {
			if($pathPart !== null) {
				$path[] = urlencode($pathPart);
			} else {
				$path[] = '_';
			}
		}
		
		while(count($path)) {
			$part = array_pop($path);
			if($part === '0' || $part === '_' || $part === '') {
				continue;
			}
			$path[] = $part;
			break;
		}
		
		$event = new BuildParamsEvent(queryParams: $queryParams);
		$this->dispatcher->dispatch($event);
		
		$parts = [implode('/', $path)];
		if(count($event->queryParams)) {
			$query = http_build_query($event->queryParams);
			if(trim((string) $query) !== '') {
				$parts[] = $query;
			}
		}
		
		return implode('?', $parts);
	}
	
	/**
	 * @param string $requestUri
	 * @return array{null|string, array<int|string, mixed>}
	 */
	private function parseUrl(string $requestUri): array {
		$path = parse_url($requestUri, PHP_URL_PATH);
		$query = $this->parseQuery($requestUri);
		if(array_key_exists('actions', $query)) {
			// `actions` is reserved for Action-Plugins
			unset($query['actions']);
		}
		return [$path ?: null, $query];
	}
	
	/**
	 * @param string $requestUri
	 * @return array<int|string, string|mixed[]>
	 */
	private function parseQuery(string $requestUri): array {
		$query = parse_url($requestUri, PHP_URL_QUERY);
		$res = [];
		parse_str((string) $query, $res);
		return $res;
	}
	
	/**
	 * @param bool $https
	 * @return string
	 */
	private function buildHostname(bool $https): string {
		$hostnameWithSchema = '';
		
		if($this->httpHost) {
			$http = 'http';
			$hostnameWithSchema = "$http://{$this->httpHost}";
			
			if($https || $this->isHttps) {
				$hostnameWithSchema = "https://{$this->httpHost}";
			}
		}
		
		$event = new BuildHostnameWithSchemaEvent(
			hostname: $this->httpHost,
			isHttps: $this->isHttps,
			result: $hostnameWithSchema
		);
		
		$this->dispatcher->dispatch($event);
		
		return '';
	}
}
