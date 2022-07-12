<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Monorepo\Tests\unit\ValueObject;

use Codeception\Test\Unit;
use Snicco\Enterprise\Monorepo\ValueObject\Tag;
use \InvalidArgumentException;

final class TagTest extends Unit
{
    
    /**
     * @test
     */
    public function that_exceptions_are_thrown_for_bad_version_prefix() :void
    {
        new Tag('v1.0.0'); // ok
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('vv1.0.0');
        new Tag('vv1.0.0');
    }
    
    /**
     * @test
     */
    public function that_exceptions_are_thrown_for_missing_patch() :void
    {
        new Tag('1.0.0'); // ok
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('1.0');
        new Tag('1.0');
    }
    
    
    /**
     * @test
     */
    public function that_it_works_with_major_release() :void
    {
        $tag = new Tag('1.0.0');
        $this->assertTrue($tag->isMajor());
        $this->assertFalse($tag->isMinor());
        $this->assertFalse($tag->isPatch());
    
        $tag = new Tag('10.0.0');
        $this->assertTrue($tag->isMajor());
        $this->assertFalse($tag->isMinor());
        $this->assertFalse($tag->isPatch());
    
        $tag = new Tag('100.0.0');
        $this->assertTrue($tag->isMajor());
        $this->assertFalse($tag->isMinor());
        $this->assertFalse($tag->isPatch());
    }
    
    /**
     * @test
     */
    public function that_it_works_with_minor_releases() :void
    {
        $tag = new Tag('1.1.0');
        
        $this->assertFalse($tag->isMajor());
        $this->assertTrue($tag->isMinor());
        $this->assertFalse($tag->isPatch());
    
        $tag = new Tag('1.10.0');
    
        $this->assertFalse($tag->isMajor());
        $this->assertTrue($tag->isMinor());
        $this->assertFalse($tag->isPatch());
    
        $tag = new Tag('10.10.0');
    
        $this->assertFalse($tag->isMajor());
        $this->assertTrue($tag->isMinor());
        $this->assertFalse($tag->isPatch());
    
        $tag = new Tag('100.100.0');
    
        $this->assertFalse($tag->isMajor());
        $this->assertTrue($tag->isMinor());
        $this->assertFalse($tag->isPatch());
    }
    
    /**
     * @test
     */
    public function that_it_works_with_patch_releases() :void
    {
        $tag = new Tag('1.0.1');
    
        $this->assertFalse($tag->isMajor());
        $this->assertFalse($tag->isMinor());
        $this->assertTrue($tag->isPatch());
    
        $tag = new Tag('1.0.10');
    
        $this->assertFalse($tag->isMajor());
        $this->assertFalse($tag->isMinor());
        $this->assertTrue($tag->isPatch());
    
        $tag = new Tag('1.0.100');
    
        $this->assertFalse($tag->isMajor());
        $this->assertFalse($tag->isMinor());
        $this->assertTrue($tag->isPatch());
        
        $tag = new Tag('1.1.1');
        
        $this->assertFalse($tag->isMajor());
        $this->assertFalse($tag->isMinor());
        $this->assertTrue($tag->isPatch());
        
        $tag = new Tag('1.10.1');
        
        $this->assertFalse($tag->isMajor());
        $this->assertFalse($tag->isMinor());
        $this->assertTrue($tag->isPatch());
        
        $tag = new Tag('10.10.1');
        
        $this->assertFalse($tag->isMajor());
        $this->assertFalse($tag->isMinor());
        $this->assertTrue($tag->isPatch());
        
        $tag = new Tag('100.100.1');
        
        $this->assertFalse($tag->isMajor());
        $this->assertFalse($tag->isMinor());
        $this->assertTrue($tag->isPatch());
    
        $tag = new Tag('100.100.100');
    
        $this->assertFalse($tag->isMajor());
        $this->assertFalse($tag->isMinor());
        $this->assertTrue($tag->isPatch());
    }
    
}