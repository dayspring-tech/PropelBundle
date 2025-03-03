<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\ClassMethod\OptionalParametersAfterRequiredRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()
    ->withSets([
        LevelSetList::UP_TO_PHP_80,
        SymfonySetList::SYMFONY_54,
        SymfonySetList::SYMFONY_60,
    ])
    ->withSkip([
        __DIR__ . '/vendor',
        __DIR__ . '/Model/map',
        __DIR__ . '/Model/om',
        __DIR__ . '/var/cache',
        __DIR__ . '/Resources/skeleton',
        __DIR__ . '/Tests/Resources/cache',
        OptionalParametersAfterRequiredRector::class, // this re-orders parameters and requires updating calls to those functions. this would produce backward-incompatible changes
    ])
    ->withPaths([__DIR__]);
