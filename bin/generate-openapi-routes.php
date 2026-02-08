<?php

declare(strict_types=1);

use Kir\Http\Routing\OpenAPI\RouteConfigGenerator;
use Psr\Log\NullLogger;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);

$usage = <<<TXT
Usage:
  php bin/generate-openapi-routes.php [--index-path <path>] [--route-config-path <path>] <scan-path> [scan-path ...]

Parameters:
  --index-path <path>         Pfad zur Index-Datei (alternativ ENV OPENAPI_INDEX_PATH).
  --route-config-path <path>  Pfad zur generierten Route-Config (alternativ ENV OPENAPI_ROUTE_CONFIG_PATH).
  <scan-path>                 Ein oder mehrere Pfade, die nach OpenAPI-Attributen gescannt werden.

Hinweise:
  - Ohne Parameter oder mit --help wird diese Hilfe angezeigt.
  - Wenn OPENAPI_INDEX_PATH nicht gesetzt ist, ist --index-path erforderlich.
  - Wenn OPENAPI_ROUTE_CONFIG_PATH nicht gesetzt ist, ist --route-config-path erforderlich.
TXT;

$argv = $_SERVER['argv'] ?? [];
if(!is_array($argv)) {
	$argv = [];
}
$argv = array_values(array_filter($argv, static fn($arg): bool => is_string($arg)));
$args = array_slice($argv, 1);
if($args === [] || in_array('--help', $args, true)) {
	fwrite(STDOUT, $usage . PHP_EOL);
	exit($args === [] ? 1 : 0);
}

$indexPathArg = null;
$routeConfigPathArg = null;
$scanPaths = [];

for($i = 0; $i < count($args); $i++) {
	$arg = $args[$i];

	if($arg === '--index-path') {
		$indexPathArg = $args[$i + 1] ?? null;
		$i++;
		continue;
	}

	if(str_starts_with($arg, '--index-path=')) {
		$indexPathArg = substr($arg, strlen('--index-path='));
		continue;
	}

	if($arg === '--route-config-path') {
		$routeConfigPathArg = $args[$i + 1] ?? null;
		$i++;
		continue;
	}

	if(str_starts_with($arg, '--route-config-path=')) {
		$routeConfigPathArg = substr($arg, strlen('--route-config-path='));
		continue;
	}

	$scanPaths[] = $arg;
}

if($scanPaths === []) {
	fwrite(STDERR, $usage . PHP_EOL);
	exit(1);
}

$scanPaths = array_values(array_filter(
	array_map(static fn(string $path): string => trim($path), $scanPaths),
	static fn(string $path): bool => $path !== ''
));
$scanPaths = array_map(
	static fn($path) => str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : $root . '/' . $path,
	$scanPaths
);

$indexPath = getenv('OPENAPI_INDEX_PATH');
if($indexPath === false || $indexPath === '') {
	$indexPath = null;
}

$routeConfigPath = getenv('OPENAPI_ROUTE_CONFIG_PATH');
if($routeConfigPath === false || $routeConfigPath === '') {
	$routeConfigPath = null;
}

if(is_string($indexPathArg) && $indexPathArg !== '') {
	$indexPath = $indexPathArg;
}

if(is_string($routeConfigPathArg) && $routeConfigPathArg !== '') {
	$routeConfigPath = $routeConfigPathArg;
}

if($indexPath === null) {
	fwrite(STDERR, $usage . PHP_EOL);
	exit(1);
}

if($routeConfigPath === null) {
	fwrite(STDERR, $usage . PHP_EOL);
	exit(1);
}

$generator = new RouteConfigGenerator(
	indexPath: $indexPath,
	scanPaths: $scanPaths,
	routeConfigFilePath: $routeConfigPath,
	logger: new NullLogger()
);

$generator->generate();
