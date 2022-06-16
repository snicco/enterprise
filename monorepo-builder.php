<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\ComposerJsonManipulator\ValueObject\ComposerJsonSection;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\SetCurrentMutualConflictsReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\SetCurrentMutualDependenciesReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\UpdateReplaceReleaseWorker;
use Symplify\MonorepoBuilder\ValueObject\Option;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set(Option::DEFAULT_BRANCH_NAME, 'master');

    $parameters->set(Option::PACKAGE_DIRECTORIES, [
        __DIR__ . '/src/Snicco/bundle',
        __DIR__ . '/src/Snicco/component',
        __DIR__ . '/src/Snicco/plugin',
        //__DIR__ . '/src/Snicco/skeleton',
    ]);

    $parameters->set(Option::DATA_TO_APPEND, [
        ComposerJsonSection::REQUIRE => [
            'php' => '^7.4|^8.0',
        ],
        ComposerJsonSection::REQUIRE_DEV => [
            // Codeception dependencies
            'codeception/codeception' => '4.2',
            'codeception/module-cli' => '^1.0',
            'codeception/module-db' => '^1.0',
            'codeception/module-webdriver' => '^1.0',
            'phpunit/phpunit' => '9.5.13',
            'lucatume/wp-browser' => '3.1.6',
            // QA dependencies
            'vimeo/psalm' => '4.22.0',
            'symplify/easy-coding-standard' => '10.1.2',
            'rector/rector' => '0.13.5',
            // Monorepo dependencies,
            'sniccowp/php-scoper-wordpress-excludes' => '^5.9.3',
            'symplify/monorepo-builder' => '9.4.70',
            'vlucas/phpdotenv' => '5.4.1',
            'php-stubs/wordpress-stubs' => '^5.9.3',
            'symfony/console' => '5.4.7',
            'symplify/composer-json-manipulator' => '9.3.26',
        ],
        ComposerJsonSection::AUTOLOAD_DEV => [
            'psr-4' => [
                'Snicco\\Enterprise\\Monorepo\\' => 'src/Monorepo/',
                'Snicco\\Enterprise\\Monorepo\\Tests\\' => 'tests/',
            ],
        ],
        'minimum-stability' => 'dev',
        'prefer-stable' => true,
    ]);

    $services = $containerConfigurator->services();

    /*
     * We do only need these three release workers which run during the prepare command of the semantic release
     * node package. Before the release commit is added we update:
     * 1. the "replace" key in the root composer.json
     * 2. the "conflicts" key of each package composer.json
     * 3. all mutual dependencies between packages to the new version that is being released.
     *
     * In contrast to the classical monorepo builder approach (https://github.com/symplify/monorepo-builder#6-release-flow),
     * we do not need to increase any branch aliases or make any manual tags.
     *
     * Branch aliases are not needed because the monorepo is only split if a release is made.
     * Releases are only made for feat/fix/perf commits and thus there should never be the need for somebody to require
     * "dev-master" of a single package. "dev-master" of any package is by design always identical to
     * the latest tag of the package.
     */
    $services->set(UpdateReplaceReleaseWorker::class);
    $services->set(SetCurrentMutualConflictsReleaseWorker::class);
    $services->set(SetCurrentMutualDependenciesReleaseWorker::class);
};
