<?php

namespace DvTeam\Routing;

use DvTeam\Routing\TestSubjects\MockController;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

class RouterTest extends TestCase {
	public function testWorkingWithDefaultAlias(): void {
		$router = self::getRouter();
		$router->addRoute('a', 'a', [MockController::class, 'a'], ['GET'], [], Router::INSECURE);
		$router->addRoute('b', 'b', [MockController::class, 'b'], ['GET'], ['a', 'b' => 0], Router::INSECURE);
		$router->addRoute('c', 'c', [MockController::class, 'c'], ['GET'], [], Router::INSECURE);
		
		$router->setDefaultAlias('b');
		$defaultAlias = $router->getDefaultAlias();
		self::assertEquals('b', $defaultAlias);
	}
	
	public function testLinkTo(): void {
		$router = self::getRouter();
		$router->addRoute('a', 'a', [MockController::class, 'a'], ['GET'], [], Router::INSECURE);
		$router->addRoute('b', 'b', [MockController::class, 'b'], ['GET'], ['a', 'b' => 0], Router::INSECURE);
		$router->addRoute('c', 'c', [MockController::class, 'c'], ['GET'], [], Router::INSECURE);
		$router->addRoute('d', 'd', [MockController::class, 'd'], ['GET'], ['a'], Router::INSECURE);

		self::assertStringEndsWith('/a', $router->linkTo(['alias' => 'a']));
		self::assertStringEndsWith('/b/1/%22test%26%22?c%5B0%5D=1&c%5B1%5D=2&c%5B2%5D=3&c%5B3%5D=4', $router->linkTo(['alias' => 'b', 'a' => 1, 'b' => '"test&"', 'c' => [1, 2, 3, 4]]));
		self::assertStringEndsWith('/c?a=1&b=%22test%26%22&c%5B0%5D=1&c%5B1%5D=2&c%5B2%5D=3&c%5B3%5D=4', $router->linkTo(['alias' => 'c', 'a' => 1, 'b' => '"test&"', 'c' => [1, 2, 3, 4]]));
		self::assertStringEndsWith('/d/1?b=%22test%26%22', $router->linkTo(['alias' => 'd', 'a' => 1, 'b' => '"test&"']));
		self::assertStringEndsWith('/d/1', $router->linkTo(['alias' => 'd', 'a' => 1]));

		$router->enterContext(['ctrl' => MockController::class, 'method' => 'a'], function () use ($router) {
			self::assertStringEndsWith('/c', $router->linkTo(['method' => 'c']));
			self::assertStringEndsWith('/b', $router->linkTo(['method' => 'b']));
			self::assertStringEndsWith('/b/test', $router->linkTo(['method' => 'b', 'a' => 'test']));
		});
	}

	public function testLinkToSelf(): void {
		$router = self::getRouter();
		$router->addRoute('test-a', 'test', [MockController::class, 'a'], ['GET'], ['a', 'b', 'c'], Router::INSECURE);
		$router->addRoute('test-b', 'test', [MockController::class, 'b'], ['GET'], ['a', 'b', 'd'], Router::INSECURE);

		self::assertStringEndsWith('/test', $router->linkToSelf(['alias' => 'test-a']));
		self::assertStringEndsWith('/test/2', $router->linkToSelf(['alias' => 'test-a', 'a' => 2]));
		self::assertStringEndsWith('/test/_/_/1', $router->linkToSelf(['alias' => 'test-a', 'c' => 1]));
		self::assertStringEndsWith('/test?d=4', $router->linkToSelf(['alias' => 'test-a', 'd' => 4]));

		$router->enterContext(['alias' => 'test-a', 'a' => 1, 'b' => 2, 'c' => 3], function () use ($router) {
			self::assertStringEndsWith('/test/1/2/3', $router->linkToSelf([]));
			self::assertStringEndsWith('/test/2/2/3', $router->linkToSelf(['a' => 2]));
			self::assertStringEndsWith('/test/1/2/1', $router->linkToSelf(['c' => 1]));
			self::assertStringEndsWith('/test/1/2/4?c=3', $router->linkToSelf(['alias' => 'test-b', 'd' => 4]));
			self::assertStringEndsWith('/test/1/2/3?c=4', $router->linkToSelf(['alias' => 'test-b', 'd' => 3, 'c' => 4]));

			$router->enterContext(['c' => 6], function () use ($router) {
				self::assertStringEndsWith('/test/1/2/6', $router->linkToSelf(['alias' => 'test-a']));
				self::assertStringEndsWith('/test/1/2/3', $router->linkToSelf(['alias' => 'test-a', 'c' => 3]));
				self::assertStringEndsWith('/test/7/8/9', $router->linkToSelf(['alias' => 'test-a', 'a' => 7, 'b' => 8, 'c' => 9]));
				self::assertStringEndsWith('/test/7/8/9', $router->linkToSelf(['alias' => 'test-a', 'a' => 7, 'b' => 8, 'c' => 9]));

				$router->enterContext(['d' => 1], function () use ($router) {
					self::assertStringEndsWith('/test/1/2/6?d=1', $router->linkToSelf(['alias' => 'test-a']));
				});
			});
		});

		$router->enterContext(['alias' => 'test-a', 'b' => 2], function () use ($router) {
			self::assertStringEndsWith('/test/_/2/1', $router->linkToSelf(['method' => 'b', 'd' => 1]));
			self::assertStringEndsWith('/test/_/2?c=3', $router->linkToSelf(['method' => 'b', 'c' => 3]));
		});
	}

	public function testLinkToSelfWithCtx2(): void {
		$router = self::getRouter();
		$router->addRoute('test-a', 'test', [MockController::class, 'a'], ['GET'], ['a', 'b', 'c'], Router::INSECURE);
		$router->addRoute('test-b', 'test', [MockController::class, 'b'], ['GET'], ['a', 'b', 'd'], Router::INSECURE);

		$router->enterContext(['alias' => 'test-a', 'b' => 2], function () use ($router) {
			self::assertStringEndsWith('/test/_/2/1', $router->linkToSelf(['method' => 'b', 'd' => 1]));
			self::assertStringEndsWith('/test/_/2?c=3', $router->linkToSelf(['method' => 'b', 'c' => 3]));
		});
	}

	public function testGetCallParams(): void {
		$router = self::getRouter();
		$router->get('test', 'test', [MockController::class, 'echo'], ['a' => null, 'b' => 1, 'c' => 'abc'], Router::INSECURE);
		self::assertEquals(['test', MockController::class, 'echo', ['a' => null, 'b' => 1, 'c' => 'abc'], []], $router->getCallParams('GET', '/test'));
		self::assertEquals(['test', MockController::class, 'echo', ['a' => '1', 'b' => 1, 'c' => 'abc'], []], $router->getCallParams('GET', '/test/1'));
		self::assertEquals(['test', MockController::class, 'echo', ['a' => 1, 'b' => 1, 'c' => 'a'], []], $router->getCallParams('GET', '/test/1/_/a'));
		self::assertEquals(['test', MockController::class, 'echo', ['a' => 1, 'b' => 1, 'c' => 'a', 'd' => 1], []], $router->getCallParams('GET', '/test/1/_/a?d=1'));
		self::assertEquals(['test', MockController::class, 'echo', ['a' => 1, 'b' => 1, 'c' => 'abc', 'd' => '1'], []], $router->getCallParams('GET', '/test/1?d=1'));
		self::assertEquals(['test', MockController::class, 'echo', ['a' => 1, 'b' => 1, 'c' => 'abc'], []], $router->getCallParams('GET', '/test?a=1'));
	}
	
	private static function getRouter(): Router {
		$dispatcher = new class implements EventDispatcherInterface {
			public function dispatch(object $event) {
			}
		};
		return new Router(
			webRoot: __DIR__,
			httpHost: 'test.localhost',
			isHttps: true,
			dispatcher: $dispatcher
		);
	}
}
