<?php

declare(strict_types=1);

use Codeception\Test\Unit;
use Codeception\TestCase\WPTestCase;
use Snicco\Bundle\Testing\Functional\WebTestCase;
use Psalm\Plugin\EventHandler\AfterClassLikeVisitInterface;
use Psalm\Plugin\EventHandler\Event\AfterClassLikeVisitEvent;
use Snicco\Enterprise\Bundle\Fortress\Tests\FortressWebTestCase;

final class TestCaseClasses implements AfterClassLikeVisitInterface
{
    
    public static function afterClassLikeVisit(AfterClassLikeVisitEvent $event): void
    {
        $storage = $event->getStorage();
        
        if (!$storage->user_defined) {
            return;
        }
        
        $parents = $storage->parent_classes;
        
        if (empty($parents)) {
            return;
        }
        
        $suppress_for = [
            Unit::class,
            WPTestCase::class,
            WebTestCase::class,
            FortressWebTestCase::class,
        ];
        
        if (array_intersect($parents, $suppress_for) !== []) {
            $storage->suppressed_issues[] = 'PropertyNotSetInConstructor';
        }
    }
    
}
