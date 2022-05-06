<?php

declare(strict_types=1);

use Symfony\Component\Console\Application;
use Snicco\Enterprise\DistributedPlugin\Setup\ConfigureCommand;

try {
    set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }, E_ALL);
    
    require dirname(__DIR__).'/vendor/autoload.php';
    
    $application = new Application();
    
    $command = new ConfigureCommand(
        dirname(__DIR__),
        dirname(__DIR__).DIRECTORY_SEPARATOR.'plugin'.DIRECTORY_SEPARATOR.'composer.json'
    );
    
    $application->add($command);
    $application->setDefaultCommand($command->getName());
    
    exit($application->run());
} catch (Throwable $e) {
    echo PHP_EOL.PHP_EOL."\033[0;31m[ERROR] ".$e->getMessage()."\033[0m".PHP_EOL.PHP_EOL;
    exit(1);
}

