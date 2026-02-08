<?php

namespace Kir\Http\Routing;

use Kir\Http\Routing\Common\OpenAPI\TestProductHandler;
use Kir\Http\Routing\Common\Route;
use Kir\Http\Routing\Common\ServerRequest;
use Kir\Http\Routing\Common\Uri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @phpstan-type TData array{
 *	   name: string,
 *     method: string,
 *     path: string,
 *     target: array{class-string, string},
 *     params: array<string, mixed>
 * }
 */
class RouterBuilderTest extends TestCase {
	/**
	 * @param list<TData> $routes
	 */
	#[DataProvider('dataProvider')]
	public function testBuildFromOpenApiRoutesFile(array $routes): void {
		$builder = new RouterBuilder();
		$builder->addDefinitions(['routes' => $routes]);
		$router = $builder->build();

		$request = new ServerRequest(
			method: 'GET',
			uri: new Uri('http://test.localhost/products/gtin-by-article-number/A-12345'),
			queryParams: [],
			parsedBody: []
		);

		$route = $router->lookup($request);

		self::assertInstanceOf(Route::class, $route);
		self::assertEquals('getGtinByArticleNumber', $route->name);
		self::assertEquals('GET', $route->method);
		$allParams = $route->allParams();
		self::assertArrayHasKey('articleNumber', $allParams);
		self::assertEquals('A-12345', $allParams['articleNumber']);
		self::assertArrayHasKey('httpData', $allParams);
		self::assertEquals(
			[TestProductHandler::class, 'getGtinByArticleNumber'],
			$route->callable
		);
		self::assertEquals([], $route->attributes);
	}

	/**
	 * @return array{routes: TData[]}[]
	 */
	public static function dataProvider(): array {
		return [[
			'routes' => [[
				'name' => 'getGtinByArticleNumber',
				'method' => 'GET',
				'path' => '/products/gtin-by-article-number/{articleNumber}',
				'target' => [TestProductHandler::class, 'getGtinByArticleNumber'],
				'params' => [
					'openapi' => [
						'security' => ['bearerAuth' => []]
					]
				],
			]],
		]];
	}
}
