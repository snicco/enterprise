<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Alias\NoMixedEchoPrintFixer;
use PhpCsFixer\Fixer\ClassNotation\SelfAccessorFixer;
use PhpCsFixer\Fixer\ConstantNotation\NativeConstantInvocationFixer;
use PhpCsFixer\Fixer\FunctionNotation\NativeFunctionInvocationFixer;
use PhpCsFixer\Fixer\Import\FullyQualifiedStrictTypesFixer;
use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoUselessInheritdocFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocOrderByValueFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocToCommentFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTypesOrderFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitMethodCasingFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitStrictFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestAnnotationFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestCaseStaticMethodCallsFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestClassRequiresCoversFixer;
use PhpCsFixer\Fixer\Semicolon\MultilineWhitespaceBeforeSemicolonsFixer;
use PhpCsFixer\Fixer\Whitespace\NoExtraBlankLinesFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\CodingStandard\Fixer\LineLength\DocBlockLineLengthFixer;
use Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

/*
* After php-scoper is done scoping the codebase we run easy-coding-standards
 * again to make the code look acceptable again.
*/
return static function (ContainerConfigurator $configurator): void {
    $parameters = $configurator->parameters();
    $parameters->set(Option::PATHS, [
        __DIR__ . '/src',
        __DIR__ . '/main.php',
        __DIR__ . '/PLUGIN_BASENAME.php',
        __DIR__ . '/boot',
        __DIR__ . '/uninstall.php',
    ]);

    $parameters->set(Option::PARALLEL, true);

    $services = $configurator->services();

    // Import base rules.
    $configurator->import(SetList::PHP_CS_FIXER);
    $configurator->import(SetList::PHP_CS_FIXER_RISKY);
    $configurator->import(SetList::PSR_12);
    $configurator->import(SetList::SPACES);
    $configurator->import(SetList::ARRAY);
    $configurator->import(SetList::DOCBLOCK);
    $configurator->import(SetList::CLEAN_CODE);
    $configurator->import(SetList::NAMESPACES);
    $configurator->import(SetList::STRICT);
    $configurator->import(SetList::COMMENTS);
    $configurator->import(SetList::SYMPLIFY);

    // Don't use @covers annotations. They are useless.
    $services->remove(PhpUnitTestClassRequiresCoversFixer::class);

    // Don't turn inline psalm annotations to comments.
    // @see https://github.com/FriendsOfPHP/PHP-CS-Fixer/issues/4446
    $services->set(PhpdocToCommentFixer::class)->call('configure', [
        [
            'ignored_tags' => ['psalm-suppress', 'var', 'psalm-var'],
        ],
    ]);

    $services->set(MultilineWhitespaceBeforeSemicolonsFixer::class)->call('configure', [
        [
            'strategy' => 'no_multi_line',
        ],
    ]);

    // PHPUnit test methods must be snake_case
    $services->set(PhpUnitMethodCasingFixer::class)->call('configure', [
        [
            'case' => 'snake_case',
        ],
    ]);

    // Don't sort parameters or psalm will get confused for something like @param Closure():foo|string $param
    $services->set(PhpdocTypesOrderFixer::class)->call('configure', [
        [
            'null_adjustment' => 'always_last',
            'sort_algorithm' => 'none',
        ],
    ]);

    $services->set(NoExtraBlankLinesFixer::class)->call('configure', [
        [
            'tokens' => [
                //                'use', Allow blank lines in import statements to separate functions/classes/constants
                'break',
                'case',
                'continue',
                'curly_brace_block',
                'default',
                'extra',
                'parenthesis_brace_block',
                'return',
                'square_brace_block',
                'switch',
                'throw',
                'use_trait',
            ],
        ],
    ]);

    // Test methods should have an annotation and not be prefixed with "test_"
    $services->set(PhpUnitTestAnnotationFixer::class)->call('configure', [
        [
            'style' => 'annotation',
        ],
    ]);

    // Assertions should be called with $this instead of self::
    $services->set(PhpUnitTestCaseStaticMethodCallsFixer::class)->call('configure', [
        [
            'call_type' => 'this',
        ],
    ]);

    // Only echo, no print.
    $services->set(NoMixedEchoPrintFixer::class);

    // Allows use the short class name for imported classes
    $services->set(FullyQualifiedStrictTypesFixer::class);

    // Important native PHP constants for a little speed boost
    $services->set(NativeConstantInvocationFixer::class);

    // Important native PHP functions for a little speed boost
    $services->set(NativeFunctionInvocationFixer::class)->call('configure', [
        [
            'include' => ['@all'],
        ],
    ]);

    // Everything must be imported.
    $services->set(GlobalNamespaceImportFixer::class)->call('configure', [
        [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
    ]);

    // Remove unused imports.
    $services->set(NoUnusedImportsFixer::class);

    // Order imports by class/function/constant
    $services->set(OrderedImportsFixer::class)->call('configure', [
        [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const'],
        ],
    ]);

    // Remove useless phpdoc annotations. GIT handles this way better.
    $services->set(GeneralPhpdocAnnotationRemoveFixer::class)->call(
        'configure',
        [
            [
                'annotations' => [
                    //                   'throws', Allow @throws annotation.
                    'author',
                    'package',
                    'group',
                    'covers',
                    'since',
                ],
            ],
        ]
    );

    // Remove useless @inheritdoc annotations. All IDEs understand this.
    $services->set(PhpdocNoUselessInheritdocFixer::class);

    // Enforce a line length
    $services->set(LineLengthFixer::class)->call('configure', [
        [
            LineLengthFixer::LINE_LENGTH => 120,
            LineLengthFixer::BREAK_LONG_LINES => true,
            LineLengthFixer::INLINE_SHORT_LINES => true,
        ],
    ]);

    // Enforce a docblock line length
    $services->set(DocBlockLineLengthFixer::class)->call('configure', [
        [
            DocBlockLineLengthFixer::LINE_LENGTH => 80,
        ],
    ]);

    // Order annotations
    $services->set(PhpdocOrderByValueFixer::class)->call('configure', [
        [
            'annotations' => ['internal', 'throws'],
        ],
    ]);

    // This is a little risky.
    $services->remove(PhpUnitStrictFixer::class);

    // Allow class names inside same class
    $services->remove(SelfAccessorFixer::class);
};
