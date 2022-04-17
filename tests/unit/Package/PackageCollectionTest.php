<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Monorepo\Tests\unit\Package;

use Codeception\Test\Unit;
use Snicco\Enterprise\Monorepo\Package\Package;
use Snicco\Enterprise\Monorepo\Package\PackageRepository;
use Snicco\Enterprise\Monorepo\Package\PackageCollection;
use Snicco\Enterprise\Monorepo\ValueObject\RepositoryRoot;

final class PackageCollectionTest extends Unit
{
    private string            $fixtures_dir;
    private PackageRepository $package_repo;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->fixtures_dir = dirname(__DIR__,2).'/fixtures';
        $this->package_repo = new PackageRepository([
            $this->fixtures_dir.'/packages/bundle',
            $this->fixtures_dir.'/packages/plugin',
        ], new RepositoryRoot($this->fixtures_dir));
    }
    
    /**
     * @test
     */
    public function that_packages_are_unique() :void
    {
        $package_a = $this->package_repo->get($this->fixtures_dir.'/packages/bundle/bundle-a/composer.json');
        $package_a_2 = clone $package_a;
        
        $collection = new PackageCollection([$package_a, $package_a_2]);
        
        $this->assertCount(1, $collection);
    }
    
    /**
     * @test
     */
    public function that_merge_returns_a_new_collection() :void
    {
        $package_a = $this->package_repo->get($this->fixtures_dir.'/packages/bundle/bundle-a/composer.json');
        $package_b = $this->package_repo->get($this->fixtures_dir.'/packages/bundle/bundle-b/composer.json');
    
        $collection_a = new PackageCollection([$package_a]);
        $collection_b = new PackageCollection([$package_b]);
    
        $this->assertCount(1, $collection_a);
        $this->assertCount(1, $collection_b);
     
        $merged = $collection_a->merge($collection_b);
    
        $this->assertCount(1, $collection_a);
        $this->assertCount(1, $collection_b);
        $this->assertCount(2, $merged);
    }
    
    /**
     * @test
     */
    public function that_packages_can_be_filtered_out() :void
    {
        $package_a = $this->package_repo->get($this->fixtures_dir.'/packages/bundle/bundle-a/composer.json');
        $package_b = $this->package_repo->get($this->fixtures_dir.'/packages/bundle/bundle-b/composer.json');
        
        $collection = new PackageCollection([$package_a, $package_b]);
        
        $filtered = $collection->filter(function (Package $package) {
           return $package->short_name === 'bundle-a';
        });
        
        $this->assertCount(1, $filtered);
        
    }
    
    /**
     * @test
     */
    public function that_the_collection_is_iterable() :void
    {
        $package_a = $this->package_repo->get($this->fixtures_dir.'/packages/bundle/bundle-a/composer.json');
        $package_b = $this->package_repo->get($this->fixtures_dir.'/packages/bundle/bundle-b/composer.json');
    
        $collection = new PackageCollection([$package_a, $package_b]);
    
        $a_found = false;
        $b_found = false;
        foreach ($collection as $package) {
            if($package === $package_a) {
                $a_found = true;
            }elseif ($package === $package_b) {
                $b_found = true;
            }
        }
        
        $this->assertTrue($a_found);
        $this->assertTrue($b_found);
        
    }
    
    /**
     * @test
     */
    public function that_the_collection_can_be_json_encoded() :void
    {
        $package_a = $this->package_repo->get($this->fixtures_dir.'/packages/bundle/bundle-a/composer.json');
        $package_b = $this->package_repo->get($this->fixtures_dir.'/packages/bundle/bundle-b/composer.json');
    
        $collection = new PackageCollection([$package_a, $package_b]);
    
        $result = $collection->toJson(function (Package $package) {
           return [
             'short_name' => $package->short_name
           ];
        });
        
        $array = (array) json_decode($result, true, JSON_THROW_ON_ERROR);
        
        $this->assertSame([
            [
                'short_name' => 'bundle-a'
            ],
            [
                'short_name' => 'bundle-b'
            ]
        ], $array);
        
    }
    
    
}