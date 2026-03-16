<?php

declare(strict_types=1);

use Rector\Config\Level\TypeDeclarationLevel;
use Rector\Config\RectorConfig;
use Rector\Php82\Rector\Param\AddSensitiveParameterAttributeRector;

$publicApiTypeDeclarationSkips = array_fill_keys(TypeDeclarationLevel::RULES, [
    __DIR__ . '/src/Client.php',
    __DIR__ . '/src/Config.php',
    __DIR__ . '/src/Resource/*',
]);

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withoutParallel()
    ->withPhpSets(php84: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        instanceOf: true,
        phpunitCodeQuality: true,
    )
    ->withConfiguredRule(AddSensitiveParameterAttributeRector::class, [
        AddSensitiveParameterAttributeRector::SENSITIVE_PARAMETERS => ['apiKey', 'secret'],
    ])
    ->withSkip($publicApiTypeDeclarationSkips)
    ->withComposerBased(phpunit: true);
