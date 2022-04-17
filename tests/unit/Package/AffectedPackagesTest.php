<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Monorepo\Tests\unit\Package;

use Codeception\Test\Unit;
use Snicco\Enterprise\Monorepo\Package\Package;
use Snicco\Enterprise\Monorepo\Package\PackageRepository;
use Snicco\Enterprise\Monorepo\Package\PackageCollection;
use Snicco\Enterprise\Monorepo\ValueObject\RepositoryRoot;

final class AffectedPackagesTest extends Unit
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
    public function that_it_returns_an_empty_collection_for_empty_input() :void
    {
        $packages = $this->package_repo->getAffected([]);
        $this->assertEquals(new PackageCollection([]), $packages);
    }
    
    /**
     * @test
     */
    public function that_it_returns_an_empty_collection_for_changed_files_not_in_packages_folder(): void
    {
        $packages = $this->package_repo->getAffected([$this->fixtures_dir.'/foo.php']);
        $this->assertEquals(new PackageCollection([]), $packages);
        
        $packages = $this->package_repo->getAffected(['/foo.php']);
        $this->assertEquals(new PackageCollection([]), $packages);
    }
    
    /**
     * @test
     */
    public function that_a_package_is_marked_as_affected_if_at_least_one_file_in_the_package_was_changed(): void
    {
        $packages = $this->package_repo->getAffected(
            [$this->fixtures_dir.'/packages/plugin/plugin-b/composer.json']
        );
        
        $this->assertCount(1, $packages);
        $this->assertSame([
            [
                'short_name' => 'plugin-b'
            ]
        ], json_decode($packages->toJson(fn(Package $package) => [
            'short_name' => $package->short_name
        ]), true ));
        
        $packages = $this->package_repo->getAffected(['/packages/plugin/plugin-b/composer.json']);
    
        $this->assertCount(1, $packages);
        $this->assertSame([
            [
                'short_name' => 'plugin-b'
            ]
        ], json_decode($packages->toJson(fn(Package $package) => [
            'short_name' => $package->short_name
        ]),true ) );
    
    }
    
    /**
     * @test
     */
    public function that_dependencies_are_resolved_from_composer_json_and_added_to_the_list_of_affected_packages(): void
    {
        // dependencies in require
        $packages = $this->package_repo->getAffected(
            [$this->fixtures_dir.'/packages/bundle/bundle-b/composer.json']
        );
        
        $this->assertCount(2, $packages);
        $this->assertSame([
            [
                'short_name' => 'bundle-b'
            ],
            [
                'short_name' => 'plugin-b'
            ]
        ], json_decode($packages->toJson(fn(Package $package) => [
            'short_name' => $package->short_name
        ]), true ));
    
        // dependencies in require-dev
        $packages = $this->package_repo->getAffected(
            [$this->fixtures_dir.'/packages/bundle/bundle-a/composer.json']
        );
    
        $this->assertCount(2, $packages);
        $this->assertSame([
            [
                'short_name' => 'bundle-a'
            ],
            [
                'short_name' => 'plugin-b'
            ]
        ], json_decode($packages->toJson(fn(Package $package) => [
            'short_name' => $package->short_name
        ]), true ));
    
    }
    
}