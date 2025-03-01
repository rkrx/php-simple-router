<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\Php80\Rector\FunctionLike\MixedTypeRector;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/config',
        __DIR__ . '/controllers',
        __DIR__ . '/plugins',
        __DIR__ . '/public/index.php',
        __DIR__ . '/scripts',
        __DIR__ . '/src',
        __DIR__ . '/src-ext',
        __DIR__ . '/tests',
        __DIR__ . '/widgets',
    ]);

    // register a single rule
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

	$rectorConfig->skip([RemoveUselessParamTagRector::class]);
	$rectorConfig->skip([MixedTypeRector::class]);

	// define sets of rules
	$rectorConfig->sets([
		LevelSetList::UP_TO_PHP_83
	]);
};
