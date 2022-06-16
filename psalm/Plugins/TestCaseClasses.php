<?php

declare(strict_types=1);

use Codeception\Test\Unit;
use Codeception\TestCase\WPTestCase;
use PHPUnit\Runner\AfterTestErrorHook;
use Psalm\Plugin\EventHandler\AfterFunctionCallAnalysisInterface;
use Psalm\Plugin\EventHandler\AfterMethodCallAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterFunctionCallAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\AfterMethodCallAnalysisEvent;
use Snicco\Bundle\Testing\Functional\WebTestCase;
use Psalm\Plugin\EventHandler\AfterClassLikeVisitInterface;
use Psalm\Plugin\EventHandler\Event\AfterClassLikeVisitEvent;
use Snicco\Component\StrArr\Str;
use Snicco\Enterprise\Bundle\Fortress\Tests\cli\Auth\TwoFactor\Initialize2FaCest;
use Snicco\Enterprise\Bundle\Fortress\Tests\FortressWebTestCase;

final class TestCaseClasses implements AfterClassLikeVisitInterface
{
    
    public static function afterClassLikeVisit(AfterClassLikeVisitEvent $event): void
    {
        $storage = $event->getStorage();
        
        if (!$storage->user_defined) {
            return;
        }
    
        $is_cest = Str::endsWith($storage->name, 'Cest');
        $is_cept = Str::endsWith($storage->name, 'Cept');
        
        $parents = $storage->parent_classes;
        
        if (empty($parents) && ! $is_cept && !$is_cest) {
            return;
        }
        
        $suppress_for = [
            Unit::class,
            WPTestCase::class,
            WebTestCase::class,
            FortressWebTestCase::class,
        ];
        
        if (array_intersect($parents, $suppress_for) !== [] || $is_cept || $is_cest) {
            $storage->suppressed_issues[] = 'PropertyNotSetInConstructor';
        }
    }
    
}
