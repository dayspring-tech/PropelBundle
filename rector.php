<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php73\Rector\FuncCall\JsonThrowOnErrorRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonyLevelSetList;
use Rector\Symfony\Set\SymfonySetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->skip([
        __DIR__ . '/vendor',
        __DIR__ . '/Model/map',
        __DIR__ . '/Model/om',
        __DIR__ . '/var/cache',
        __DIR__ . '/Resources/skeleton',
        __DIR__ . '/Tests/Resources/cache',
    ]);

    $rectorConfig->paths([
        __DIR__,
    ]);

    // register a single rule
//    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    // define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
        SetList::PHP_82,
        SymfonySetList::SYMFONY_54,
        SymfonySetList::SYMFONY_60,
        SymfonySetList::SYMFONY_61,
//        SymfonySetList::SYMFONY_62,
//        SymfonySetList::SYMFONY_63,
//        SymfonySetList::SYMFONY_64,
    ]);
};
