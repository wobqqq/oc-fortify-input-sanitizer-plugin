<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/Plugin.php',
        __DIR__ . '/cache',
        __DIR__ . '/console',
        __DIR__ . '/dto',
        __DIR__ . '/http',
        __DIR__ . '/instances',
        __DIR__ . '/listeners',
        __DIR__ . '/services',
        __DIR__ . '/transformers',
        __DIR__ . '/validator',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/tests/Stubs',
    ])
    ->withPhpSets(php82: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        earlyReturn: true,
    );
