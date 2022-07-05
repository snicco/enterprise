<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Monorepo;

use Symfony\Component\Finder\Finder;

use function array_keys;
use function dirname;

final class ExcludedQADirectories
{
    /**
     * @return string[]
     */
    public static function vendor() :array
    {
        return array_keys(iterator_to_array(
            Finder::create()
                  ->in(dirname(__DIR__, 2).'/src/Snicco')
                  ->directories()
                  ->depth('< 3')
                  ->name('vendor')
                  ->append([dirname(__DIR__, 2).'/vendor'])
        ));
    }
    
    /**
     * @return string[]
     */
    public static function generatedFiles() :array
    {
        return array_keys(iterator_to_array(
            Finder::create()
                  ->in(dirname(__DIR__, 2).'/src/Snicco/*/*/tests/_support')
                  ->directories()
                  ->name('_generated')
        ));
    }
    
    
    
}