<?php

declare(strict_types=1);

use Snicco\Enterprise\Monorepo\PHPScoper\BuildVersion;

require_once dirname(__DIR__, 4).'/src/Monorepo/PHPScoper/BuildVersion.php';

/**
 * Composer handles autoloaded files by generating a hash based on the package-name
 * that includes the file.
 *
 * Each unique hash will only be included once by the composer autoloader.
 * This means that if one of our plugins uses a package with autoloaded files
 * there is the same possibility of race conditions if that package is used in user land elsewhere.
 *
 * What we do here is changing the generated hash for our dependencies in our autoloder.
 * We need to do that in two files:
 * 1) vendor/composer/autoload_files.php
 * 1) vendor/composer/autoload_static.php
 *
 * @see https://github.com/composer/composer/issues/7942#issuecomment-459409836
 * @see https://github.com/humbug/php-scoper/issues/298
 */

function getFileContents(string $file) :string
{
    $contents = \file_get_contents($file);
    
    if (false === $contents) {
        echo "Could not get contents of file {$file}\n";
        exit(1);
    }
    
    return $contents;
}

function putFileContents(string $file, string $contents) :void
{
    $res = \file_put_contents($file, $contents);
    
    if (false === $res) {
        echo "Could not update contents up file {$file}\n";
        exit(1);
    }
}

function pregReplace(string $pattern, string $replacement, string $subject) :string
{
    $res = \preg_replace($pattern, $replacement, $subject);
    
    if (null === $res) {
        echo "preg_replace failed.\n";
        exit(1);
    }
    
    if($res === $subject){
        echo "preg_replace did not change anything.\n";
        exit(1);
    }
    
    return $res;
}

$composer_directory = (string)($_SERVER['argv'][1] ?? '');

if ( ! \is_dir($composer_directory) || ! is_file($composer_directory.'/autoload_static.php')) {
    echo "Invalid composer directory [{$composer_directory}] provided.\n";
    exit(1);
}

$prefix = (string) new BuildVersion();

$static_loader_path = $composer_directory.'/autoload_static.php';
$static_loader_contents = \getFileContents($static_loader_path);
$static_loader_contents = \pregReplace(
    '/\'([A-Za-z0-9]*?)\' => __DIR__ \. (.*?),/',
    \sprintf('\'%s_$1\' => __DIR__ . $2,', $prefix),
    $static_loader_contents
);
\file_put_contents($static_loader_path, $static_loader_contents);

$files_loader_path = $composer_directory.'/autoload_files.php';
$autoload_files_content = \getFileContents($files_loader_path);
$autoload_files_content = \pregReplace(
    '/\'(.*?)\' => (.*?),/',
    \sprintf('\'%s_$1\' => $2,', $prefix),
    $autoload_files_content
);
\putFileContents($files_loader_path, $autoload_files_content);
