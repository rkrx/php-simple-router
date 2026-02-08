<?php

declare(strict_types=1);

use Kir\Http\Routing\OpenAPI\RouteConfigGenerator;
use Psr\Log\NullLogger;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);

$scanPaths = getenv('OPENAPI_SCAN_PATHS');
if(is_string($scanPaths) && $scanPaths !== '') {
	$scanPaths = array_map('trim', explode(',', $scanPaths));
} else {
	$scanPaths = [$root . '/tests/Common/OpenAPI'];
}

$scanPaths = array_values(array_filter($scanPaths, static fn($path) => is_string($path) && $path !== ''));
$scanPaths = array_map(
	static fn($path) => str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : $root . '/' . $path,
	$scanPaths
);

$indexPath = getenv('OPENAPI_INDEX_PATH') ?: $root . '/var/openapi-index.xml';
$routeConfigPath = getenv('OPENAPI_ROUTE_CONFIG_PATH') ?: $root . '/var/openapi-routes.php';

$generator = new RouteConfigGenerator(
	indexPath: $indexPath,
	scanPaths: $scanPaths,
	routeConfigFilePath: $routeConfigPath,
	logger: new NullLogger()
);

$generator->generate();
