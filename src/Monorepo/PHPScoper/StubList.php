<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Monorepo\PHPScoper;

use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final class StubList
{
    private string $monorepo_root;
    
    public function __construct(string $monorepo_root) {
    
        if(!is_dir($monorepo_root) || ! is_dir($monorepo_root.'/src/Snicco')){
            throw new RuntimeException("$monorepo_root is not the root of the monorepo.");
        }
        
        $this->monorepo_root = $monorepo_root;
    }
    
    /**
     * @return string[]
     */
    public function wordpress(string $name) :array
    {
        $file = $this->monorepo_root.'/vendor/sniccowp/php-scoper-wordpress-excludes/generated/'.$name;
        $contents = \file_get_contents($file);
        if (false === $contents) {
            throw new RuntimeException("Could not get contents of file {$file}");
        }
    
        $stubs = \json_decode($contents, true, \JSON_THROW_ON_ERROR);
    
        if ( ! is_array($stubs)) {
            throw new RuntimeException('Stub file contents must be an array.');
        }
        /** @var string[] $stubs */
        return $stubs;
    }
    
    /**
     * @return string[]
     */
    public function symfonyPolyFills(string $plugin_dir) :array
    {
        /** @var string[] $polyfills_bootstraps */
        $polyfills_bootstraps = \array_map(
            static fn (SplFileInfo $fileInfo) => $fileInfo->getPathname(),
            \iterator_to_array(
                Finder::create()
                      ->in($plugin_dir . '/vendor/symfony')
                      ->files()
                      ->path('/^polyfill-[\w\-_]+/')
                      ->name('bootstrap*.php'),
                false,
            ),
        );
    
        /** @var string[] $polyfills_stubs */
        $polyfills_stubs = \array_map(
            static fn (SplFileInfo $fileInfo) => $fileInfo->getPathname(),
            \iterator_to_array(
                Finder::create()
                      ->in($plugin_dir . '/vendor/symfony')
                      ->files()
                      ->path('/^polyfill-[\w\-_]+\/Resources/')
                      ->name('*.php'),
            ),
        );
    
        return \array_values(\array_merge($polyfills_bootstraps, $polyfills_stubs));
    }
    
}