<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Array_\ArrayThisCallToThisMethodCallRector;
use Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\Class_\AddArrayDefaultToArrayPropertyRector;
use Rector\CodingStyle\Rector\ClassMethod\UnSpreadOperatorRector;
use Rector\Core\Configuration\Option;
use Rector\DeadCode\Rector\Cast\RecastingRemovalRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\DeadCode\Rector\Concat\RemoveConcatAutocastRector;
use Rector\PHPUnit\Rector\Class_\AddSeeTestAnnotationRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Privatization\Rector\Class_\FinalizeClassesWithoutChildrenRector;
use Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector;
use Rector\Privatization\Rector\MethodCall\PrivatizeLocalGetterToPropertyRector;
use Rector\Privatization\Rector\Property\ChangeReadOnlyPropertyWithDefaultValueToConstantRector;
use Rector\Privatization\Rector\Property\PrivatizeFinalClassPropertyRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddArrayReturnDocTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeByMethodCallTypeRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator): void {
    $parameters = $configurator->parameters();
    $parameters->set(Option::PATHS, [
        __DIR__ . '/src/Snicco/component',
        __DIR__ . '/src/Snicco/bundle',
        __DIR__ . '/src/Snicco/plugin',
        __DIR__ . '/monorepo-builder.php',
        __DIR__ . '/rector.php',
        __DIR__ . '/ecs.php',
    ]);
    $parameters->set(Option::SKIP, [
        __DIR__ . '/src/Snicco/bundle/fortress-bundle/tests/_support/_generated',
    ]);
    
    $parameters->set(Option::PARALLEL, true);
    $parameters->set(Option::PHP_VERSION_FEATURES, '7.4');
    $parameters->set(Option::AUTO_IMPORT_NAMES, true);
    $parameters->set(Option::IMPORT_SHORT_CLASSES, true);

    $services = $configurator->services();

    // This list can be imported as is.
    $configurator->import(LevelSetList::UP_TO_PHP_74);

    $configurator->import(SetList::CODE_QUALITY);
    // Will break everywhere Controller are used.
    $services->remove(CallableThisArrayToAnonymousFunctionRector::class);
    $services->remove(ArrayThisCallToThisMethodCallRector::class);

    $configurator->import(SetList::TYPE_DECLARATION);
    // This changes doc-blocks based on inferred calls to the method.
    $services->remove(ParamTypeByMethodCallTypeRector::class);
    // This causes a lot of trouble with psalm and classes that implement an interface.
    // Maybe revisit later.
    $services->remove(AddArrayReturnDocTypeRector::class);

    $configurator->import(SetList::TYPE_DECLARATION_STRICT);

    $configurator->import(SetList::CODING_STYLE);
    // Don't want this since it only support kebabCase
    $services->remove(CatchExceptionNameMatchingTypeRector::class);
    // Break classes like ViewEngine where we rely on ... for type-checks
    $services->remove(UnSpreadOperatorRector::class);
    // Breaks typed array properties in psalm
    $services->remove(AddArrayDefaultToArrayPropertyRector::class);

    $configurator->import(SetList::EARLY_RETURN);

    $configurator->import(SetList::DEAD_CODE);
    // Breaks psalm with static and self.
    $services->remove(RemoveUselessReturnTagRector::class);
    // Does not play nicely with psalm and (string) casts
    $services->remove(RecastingRemovalRector::class);
    $services->remove(RemoveConcatAutocastRector::class);

    $configurator->import(PHPUnitSetList::PHPUNIT_CODE_QUALITY);
    // Useless. PHPStorms supports this out of the box.
    $services->remove(AddSeeTestAnnotationRector::class);

    // Parts to the SetList::PRIVATIZATION list
    $services->set(FinalizeClassesWithoutChildrenRector::class);
    $services->set(ChangeReadOnlyPropertyWithDefaultValueToConstantRector::class);
    $services->set(PrivatizeLocalGetterToPropertyRector::class);
    $services->set(PrivatizeFinalClassPropertyRector::class);
    $services->set(PrivatizeFinalClassMethodRector::class);
};
