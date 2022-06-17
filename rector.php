<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\FunctionNotation\NoUselessSprintfFixer;
use Rector\CodeQuality\Rector\Array_\ArrayThisCallToThisMethodCallRector;
use Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;
use Rector\CodeQuality\Rector\FuncCall\RemoveSoleValueSprintfRector;
use Rector\CodeQuality\Rector\FuncCall\UnwrapSprintfOneArgumentRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddArrayReturnDocTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeByMethodCallTypeRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/rector.php',
        __DIR__ . '/ecs.php',
        __DIR__ . '/monorepo-builder.php',
        __DIR__ . '/src/Monorepo',
        __DIR__ . '/src/Snicco/plugin',
        __DIR__ . '/src/Snicco/component',
        __DIR__ . '/src/Snicco/bundle',
        __DIR__ . '/bin/snicco.php',
    ]);

    $rectorConfig->cacheDirectory('/tmp/snicco-qa/rector');
    $rectorConfig->parallel();
    $rectorConfig->importShortClasses();
    $rectorConfig->importNames();
    $rectorConfig->phpVersion(PhpVersion::PHP_74);

    $rectorConfig->sets([
        SetList::PHP_74,
        SetList::CODE_QUALITY,
        SetList::TYPE_DECLARATION,
        SetList::TYPE_DECLARATION_STRICT,
        SetList::CODING_STYLE
    ]);

    $rectorConfig->skip([
        __DIR__ . '/src/Snicco/bundle/fortress-bundle/tests/_support/_generated',

        // Skip rules from SetList::CODE_QUALITY.
        // These two break controller syntax.
        CallableThisArrayToAnonymousFunctionRector::class,
        ArrayThisCallToThisMethodCallRector::class,

        // Skip rules from SetList::TYPE_DECLARATION.
        ParamTypeByMethodCallTypeRector::class,
        AddArrayReturnDocTypeRector::class,
    
        // Skip rules from SetList::CODING_STYLE.
        EncapsedStringsToSprintfRector::class, # Breaks psalm non-empty-string assertions.
        CatchExceptionNameMatchingTypeRector::class, # Does only support kebabCase.
    ]);
    
    
};
