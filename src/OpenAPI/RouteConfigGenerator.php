<?php

declare(strict_types=1);

namespace Kir\Http\Routing\OpenAPI;

use OpenApi\Attributes as OA;
use PhpLocate\Index;
use PhpLocate\UpdateIndexService;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Finder\Finder;

class RouteConfigGenerator {
	private LoggerInterface $logger;
	/** @var class-string[] */
	private array $attributeClasses;

	/**
	 * @param string $indexPath
	 * @param string[] $scanPaths
	 * @param string $routeConfigFilePath
	 * @param LoggerInterface|null $logger
	 * @param class-string[] $attributeClasses
	 */
	public function __construct(
		private readonly string $indexPath,
		private readonly array $scanPaths,
		private readonly string $routeConfigFilePath,
		?LoggerInterface $logger = null,
		array $attributeClasses = []
	) {
		$this->logger = $logger ?? new NullLogger();
		$this->attributeClasses = $attributeClasses !== [] ? $attributeClasses : [
			OA\Get::class,
			OA\Post::class,
			OA\Put::class,
			OA\Patch::class,
			OA\Delete::class,
			OA\Options::class,
			OA\Head::class,
		];
	}

	public function generate(): void {
		if(!self::openApiAttributesAvailable()) {
			$this->writeConfig([]);
			return;
		}

		$scanPaths = array_values(array_filter(
			$this->scanPaths,
			static fn(string $path) => $path !== '' && is_dir($path)
		));

		if($scanPaths === []) {
			$this->writeConfig([]);
			return;
		}

		$finder = (new Finder())
			->files()
			->in($scanPaths)
			->name('*.php');

		if(!$finder->hasResults()) {
			$this->writeConfig([]);
			return;
		}

		$this->ensureDirectoryExists(dirname($this->indexPath));

		$service = new UpdateIndexService($this->logger);
		$service->updateIndex(indexPath: $this->indexPath, files: $finder);

		$index = Index::fromFile($this->indexPath);
		$routes = [];

		foreach($this->attributeClasses as $attributeClass) {
			if(!class_exists($attributeClass)) {
				continue;
			}

			$nodes = $index->getNodes(sprintf('//attribute[@name="%s"]', $attributeClass));

			foreach($nodes as $node) {
				$parent = $node->parent();
				$grandParent = $parent->parent();
				$className = $grandParent->getAttr('name');
				$methodName = $parent->getAttr('name');

				if(!is_string($className) || $className === '' || !class_exists($className)) {
					continue;
				}

				if(!is_string($methodName) || $methodName === '') {
					continue;
				}

				$routes = array_merge(
					$routes,
					$this->buildRoutesFromAttribute($className, $methodName, $attributeClass)
				);
			}
		}

		$this->writeConfig($routes);
	}

	/**
	 * @param class-string $className
	 * @param string $methodName
	 * @param class-string $attributeName
	 * @return array<int, array<string, mixed>>
	 */
	private function buildRoutesFromAttribute(string $className, string $methodName, string $attributeName): array {
		try {
			$reflectionClass = new ReflectionClass($className);
			$reflectionMethod = $reflectionClass->getMethod($methodName);
		} catch(ReflectionException) {
			return [];
		}

		$reflectionAttributes = $reflectionMethod->getAttributes($attributeName);
		if($reflectionAttributes === []) {
			return [];
		}

		$routes = [];
		foreach($reflectionAttributes as $reflectionAttribute) {
			$attribute = $reflectionAttribute->newInstance();

			$path = $attribute->path ?? null;
			if(!is_string($path) || $path === '') {
				continue;
			}

			$httpMethod = $attribute->method ?? self::methodFromAttributeClass($attributeName);
			$httpMethod = strtoupper(is_string($httpMethod) ? $httpMethod : 'GET');

			$operationId = $attribute->operationId ?? null;
			$routeName = is_string($operationId) && $operationId !== ''
				? $operationId
				: $className . '::' . $methodName;

			$routes[] = [
				'name' => $routeName,
				'method' => $httpMethod,
				'path' => $path,
				'target' => [$className, $methodName],
				'params' => [],
			];
		}

		return $routes;
	}

	/**
	 * @param array<int, array<string, mixed>> $routes
	 */
	private function writeConfig(array $routes): void {
		$this->ensureDirectoryExists(dirname($this->routeConfigFilePath));

		$config = [
			'routes' => $routes,
		];

		$export = var_export($config, true);
		$content = <<<PHP
<?php

declare(strict_types=1);

return {$export};
PHP;

		file_put_contents($this->routeConfigFilePath, $content);
	}

	private function ensureDirectoryExists(string $directory): void {
		if($directory === '' || is_dir($directory)) {
			return;
		}

		mkdir($directory, 0775, true);
	}

	private static function openApiAttributesAvailable(): bool {
		return class_exists(OA\Get::class);
	}

	private static function methodFromAttributeClass(string $attributeClass): string {
		$shortName = strrchr($attributeClass, '\\');
		$shortName = $shortName === false ? $attributeClass : substr($shortName, 1);

		return match ($shortName) {
			'Post' => 'POST',
			'Put' => 'PUT',
			'Patch' => 'PATCH',
			'Delete' => 'DELETE',
			'Options' => 'OPTIONS',
			'Head' => 'HEAD',
			default => 'GET',
		};
	}
}
