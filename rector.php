<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Array_\ArrayThisCallToThisMethodCallRector;
use Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\Class_\AddArrayDefaultToArrayPropertyRector;
use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\DeadCode\Rector\Cast\RecastingRemovalRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\Concat\RemoveConcatAutocastRector;
use Rector\DeadCode\Rector\StaticCall\RemoveParentCallWithoutParentRector;
use Rector\PHPUnit\Rector\Class_\AddSeeTestAnnotationRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Privatization\Rector\Class_\ChangeReadOnlyVariableWithDefaultValueToConstantRector;
use Rector\Privatization\Rector\Class_\RepeatedLiteralToClassConstantRector;
use Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector;
use Rector\Privatization\Rector\Property\ChangeReadOnlyPropertyWithDefaultValueToConstantRector;
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
        SetList::CODING_STYLE,
        SetList::EARLY_RETURN,
        SetList::DEAD_CODE,
        SetList::PRIVATIZATION,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
    ]);

    $rectorConfig->skip([
        __DIR__ . '/src/Snicco/plugin/snicco-fortress/tests/_support/_generated',

        // Skip rules from SetList::CODE_QUALITY.
        CallableThisArrayToAnonymousFunctionRector::class, // These two break controller syntax.
        ArrayThisCallToThisMethodCallRector::class,

        // Skip rules from SetList::TYPE_DECLARATION.
        ParamTypeByMethodCallTypeRector::class,
        AddArrayReturnDocTypeRector::class,

        // Skip rules from SetList::CODING_STYLE.
        EncapsedStringsToSprintfRector::class, // Breaks psalm non-empty-string assertions.
        CatchExceptionNameMatchingTypeRector::class, // Does only support kebabCase.
        AddArrayDefaultToArrayPropertyRector::class, // Breaks psalm for empty array on typed array property.

        // Skip rules from SetList::DEAD_CODE.
        RecastingRemovalRector::class, // Need for psalm type safety on strictest mode
        RemoveConcatAutocastRector::class, // Need for psalm type safety on strictest mode
        RemoveParentCallWithoutParentRector::class, // Removes setUp/tearDown from test classes because WPTestCase does not have them.
        // @see https://github.com/lucatume/wp-browser/issues/583

        // Skip rules from SetList::PRIVATIZATION.
        RemoveUnusedPrivateMethodRector::class, // Breaks currently with test cases because we have to use setUp/tearDown.
        // https://github.com/lucatume/wp-browser/issues/583
        RepeatedLiteralToClassConstantRector::class, // A bit too much, especially in tests.
        PrivatizeFinalClassMethodRector::class, // Breaks because of https://github.com/lucatume/wp-browser/issues/583
        ChangeReadOnlyPropertyWithDefaultValueToConstantRector::class, // Breaks for constants in string interpolation.
        ChangeReadOnlyVariableWithDefaultValueToConstantRector::class, // Too much, especially in tests.

        // Skip rules from PHPUnitSetList::PHPUNIT_CODE_QUALITY,
        AddSeeTestAnnotationRector::class, // Useless as PHPStorm automatically support this.
    ]);
};
