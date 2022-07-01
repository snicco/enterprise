<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Monorepo\PHPScoper;

use RuntimeException;
final class ScoperPrefix
{
    private string $build_version;
    
    public function __construct() {
        if(!isset($_SERVER['BUILD_VERSION']) || ! is_string($_SERVER['BUILD_VERSION']) || '' === $_SERVER['BUILD_VERSION']) {
            throw new RuntimeException('BUILD_VERSION must be a non-empty-string.');
        }

        $build_version = sha1('snicco-'.$_SERVER['BUILD_VERSION']);
        $this->build_version = 'Scoped'.$build_version;
    }
    
    public function __toString(): string
    {
        return $this->build_version;
    }
    
}