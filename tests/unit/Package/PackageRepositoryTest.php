<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Monorepo\Tests\unit\Package;

use Codeception\Test\Unit;
use Snicco\Enterprise\Monorepo\Package\Package;
use Snicco\Enterprise\Monorepo\Package\PackageRepository;
use Snicco\Enterprise\Monorepo\Package\PackageCollection;
use Snicco\Enterprise\Monorepo\ValueObject\RepositoryRoot;

final class PackageRepositoryTest extends Unit
{
    
    private string $fixtures_dir;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->fixtures_dir = dirname(__DIR__,2).'/fixtures';
    }
    
    /**
     * @test
     */
    public function that_get_returns_a_package_by_composer_json_path() :void
    {
        $repo = $this->packageRepository();
        
        $this->assertInstanceOf(Package::class, $package = $repo->get($this->fixtures_dir.'/packages/bundle/bundle-a/composer.json'));
        $this->assertSame($this->fixtures_dir.'/packages/bundle/bundle-a', $package->absolute_directory_path);
        $this->assertSame('snicco-test/bundle-a', $package->name);
        $this->assertSame('snicco-test', $package->vendor_name);
        $this->assertSame('bundle-a', $package->short_name);
    }
    
    /**
     * @test
     */
    public function that_get_throws_an_exception_for_invalid_composer_path() :void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('file');
    
        $repo = $this->packageRepository();
        $repo->get('bogus');
    }
    
    /**
     * @test
     */
    public function that_get_all_returns_all_packages() :void
    {
        $repo = $this->packageRepository();
        
        $this->assertInstanceOf(PackageCollection::class, $collection = $repo->getAll());
        
        $this->assertCount(4, $collection);
    }
    
    
    
    /**
     * @param  non-empty-string[]  $package_dirs
     */
    private function packageRepository(array $package_dirs = null) :PackageRepository
    {
        $dirs = $package_dirs ?: [
            $this->fixtures_dir.'/packages/bundle',
            $this->fixtures_dir.'/packages/plugin',
        ];
        
        return new PackageRepository($dirs, new RepositoryRoot($this->fixtures_dir));
    }
    
    
}