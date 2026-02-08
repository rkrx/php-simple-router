<?php

namespace Kir\Http\Routing;

use Closure;
use Ioc\MethodInvoker;
use Kir\Http\Routing\Common\PreProcessRequest;
use Kir\Http\Routing\Exceptions\InvalidReturnTypeException;
use Kir\Http\Routing\Exceptions\NoPostProcessorDefinedForTypeException;
use Kir\Http\Routing\Exceptions\UndefinedRouterException;
use Kir\Http\Routing\ResponseTypes\AbstractHttpResponse;
use Kir\Http\Routing\ResponseTypes\BinaryContentResponse;
use Kir\Http\Routing\ResponseTypes\HtmlResponse;
use Kir\Http\Routing\ResponseTypes\JsonResponse;
use Kir\Http\Routing\ResponseTypes\MimeTypeContentResponse;
use Kir\Http\Routing\ResponseTypes\NotFoundResponse;
use Kir\Http\Routing\Router\MethodNotRegisteredException;
use Kir\Http\Routing\Router\RouteNotFoundException;
use Kir\Http\Routing\Router\RouterConstants;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Throwable;

/**
 * @phpstan-type HandlerType Closure(object, ResponseInterface):ResponseInterface
 */
class RouteHandler {
	private readonly RouterBuilder $routerBuilder;
	/** @var array<callable(PreProcessRequest):void> */
	private array $preProcessors = [];
	/** @var array<string, HandlerType> */
	private array $postProcessors = [];
	/** @var null|callable */
	private $errorHandler = null;

	/**
	 * @param MethodInvoker $methodInvoker
	 */
	public function __construct(private readonly MethodInvoker $methodInvoker) {
		$this->routerBuilder = new RouterBuilder();

		$this->setPostProcessor(BinaryContentResponse::class, function (BinaryContentResponse $result, ResponseInterface $response): ResponseInterface {
			$response->getBody()->write((string) $result);
			return $response
				->withStatus($result->getStatusCode())
				->withHeader('Content-Type', $result->getMimeType());
		});

		$this->setPostProcessor(HtmlResponse::class, function (HtmlResponse $result, ResponseInterface $response): ResponseInterface {
			$response->getBody()->write((string) $result);
			return $response
				->withStatus($result->getStatusCode())
				->withHeader('Content-Type', 'text/html; charset=utf-8');
		});

		$this->setPostProcessor(JsonResponse::class, function (JsonResponse $result, ResponseInterface $response): ResponseInterface {
			$response->getBody()->write(json_encode($result->getData(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
			return $response->withStatus($result->getStatusCode());
		});

		$this->setPostProcessor(MimeTypeContentResponse::class, function (MimeTypeContentResponse $result, ResponseInterface $response): ResponseInterface {
			$response->getBody()->write($result->getContent());
			return $response
				->withStatus($result->getStatusCode())
				->withHeader('Content-Type', $result->getContentMimeType());
		});

		$this->setPostProcessor(NotFoundResponse::class, function (NotFoundResponse $result, ResponseInterface $response): ResponseInterface {
			return $response->withStatus($result->getStatusCode());
		});

		#$this->setPostProcessor(RedirectionResponse::class, fn() => null);
		#$this->setPostProcessor(RedirectToHttpsResponse::class, fn() => null);
		#$this->setPostProcessor(RedirectToRefererResponse::class, fn() => null);
		#$this->setPostProcessor(RedirectToResponse::class, fn() => null);
		#$this->setPostProcessor(RedirectToSelfResponse::class, fn() => null);
		#$this->setPostProcessor(ViewResponse::class, fn() => null);

		$this->setErrorHandler(function (int $reason, string $url, string $method, ?Throwable $exception = null) {
			switch ($reason) {
				case RouterConstants::ERROR_ROUTE_NOT_FOUND:
					throw new RouteNotFoundException(url: $url, method: $method, code: $reason, previousException: $exception);
				case RouterConstants::ERROR_METHOD_NOT_REGISTERED:
					throw new MethodNotRegisteredException(url: $url, method: $method);
				case RouterConstants::ERROR_UNKNOWN:
					if($exception !== null) {
						throw $exception;
					}
					throw new UndefinedRouterException(url: $url, method: $method);
			}
		});
	}

	public function getRouter(): RouterBuilder {
		return $this->routerBuilder;
	}

	/**
	 * @param callable(PreProcessRequest):void $fn
	 * @return void
	 */
	public function addPreProcessor($fn): void {
		$this->preProcessors[] = $fn;
	}

	/**
	 * @template T of AbstractHttpResponse
	 * @param class-string<T> $className
	 * @param Closure(T, ResponseInterface):ResponseInterface $handler
	 */
	public function setPostProcessor(string $className, $handler): void {
		/** @var HandlerType $handler */
		$this->postProcessors[$className] = $handler;
	}

	/**
	 * @param callable $errorHandler
	 */
	public function setErrorHandler($errorHandler): void {
		$this->errorHandler = $errorHandler;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	public function dispatch(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
		try {
			$route = $this->routerBuilder->build()->lookup($request);
			if($route === null) {
				throw new RouteNotFoundException(
					url: (string) $request->getUri(),
					method: $request->getMethod()
				);
			}

			$callParams = $route->allParams();

			$preProcessRequest = new PreProcessRequest(handler: $route->callable);
			foreach($this->preProcessors as $preProcessFn) {
				$preProcessFn($preProcessRequest);

				if($preProcessRequest->stopPropagation) {
					break;
				}
			}

			$result = $this->methodInvoker->invoke($route->callable, $callParams);
			if(!($result instanceof AbstractHttpResponse)) {
				throw new InvalidReturnTypeException();
			}
			if(!array_key_exists($result::class, $this->postProcessors)) {
				throw new NoPostProcessorDefinedForTypeException($result::class);
			}
			$result = $this->postProcessors[$result::class]($result, $response);
			$result->getBody()->rewind();
			return $result;
		} catch(RouteNotFoundException $e) {
			http_response_code(400);
			$this->callErrorHandler(RouterConstants::ERROR_ROUTE_NOT_FOUND, $request->getMethod(), $request->getUri(), $e);
			throw $e;
		} catch(MethodNotRegisteredException $e) {
			http_response_code(400);
			$this->callErrorHandler(RouterConstants::ERROR_METHOD_NOT_REGISTERED, $request->getMethod(), $request->getUri(), $e);
			throw $e;
		} catch(Throwable $e) {
			http_response_code(500);
			$this->callErrorHandler(RouterConstants::ERROR_UNKNOWN, $request->getMethod(), $request->getUri(), $e);
			throw $e;
		}
	}

	/**
	 * @param int $reason
	 * @param string $method
	 * @param UriInterface|null $uri
	 * @param Throwable $e
	 */
	private function callErrorHandler(int $reason, string $method, ?UriInterface $uri, Throwable $e): void {
		if($this->errorHandler === null) {
			throw $e;
		}

		$data = [
			'reason' => $reason,
			'method' => $method,
			'url' => (string) ($uri ?? ''),
			'exception' => $e,
		];

		$this->methodInvoker->invoke($this->errorHandler, $data);
	}
}
