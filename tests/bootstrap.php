<?php

declare(strict_types=1);

use Dotenv\Dotenv;

require_once dirname(__DIR__, 1) . '/vendor/autoload.php';

$dot_env = Dotenv::createImmutable(__DIR__, ['.env.testing', '.env.testing.dist']);
$dot_env->load();


