<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Asset\Tests\unit;

use Codeception\Test\Unit;
use InvalidArgumentException;
use Snicco\Enterprise\Component\Asset\AssetFactory;
use Snicco\Enterprise\Component\Asset\HMRConfig;
use Snicco\Enterprise\Component\Asset\MissingManifestAsset;

use function dirname;

/**
 * @internal
 */
final class AssetFactoryTest extends Unit
{
    /**
     * @test
     */
    public function that_an_exception_is_thrown_for_invalid_public_path(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectNoticeMessage('[bogus] is not a valid manifest file.');
        new AssetFactory('/foo/bar', 'bogus',);
    }

    /**
     * @test
     */
    public function that_an_asset_can_be_created(): void
    {
        $factory = new AssetFactory('/foo/bar/public', $this->fixturesDir() . '/public/manifest.json',);

        $this->assertSame('/foo/bar/public/js/frontend.js?1234', $factory('js/frontend.js'));
        $this->assertSame('/foo/bar/public/js/frontend.js?1234', $factory('/js/frontend.js'));
    }

    /**
     * @test
     */
    public function that_it_works_without_leading_slashes_in_the_manifest(): void
    {
        $factory = new AssetFactory(
            '/foo/bar/public',
            $this->fixturesDir() . '/public/manifest-without-leading-slash.json',
        );

        $this->assertSame('/foo/bar/public/js/frontend.js?1234', $factory('js/frontend.js'));
        $this->assertSame('/foo/bar/public/js/frontend.js?1234', $factory('/js/frontend.js'));
    }

    /**
     * @test
     */
    public function that_missing_files_throw_an_exception(): void
    {
        $factory = new AssetFactory('/foo/bar/public', $this->fixturesDir() . '/public/manifest.json',);

        $this->expectException(MissingManifestAsset::class);
        $factory('foo');
    }

    /**
     * @test
     */
    public function that_passing_a_hmr_config_generates_a_compatible_url(): void
    {
        $factory = new AssetFactory(
            '/foo/bar/public',
            $this->fixturesDir() . '/public/manifest.json',
            false,
            HMRConfig::fromDefaults()
        );

        $this->assertSame('//localhost:8080/js/frontend.js', $factory('js/frontend.js'));
        $this->assertSame('//localhost:8080/js/frontend.js', $factory('/js/frontend.js'));

        $factory = new AssetFactory(
            '/foo/bar/public',
            $this->fixturesDir() . '/public/manifest.json',
            false,
            new HMRConfig('snicco.test', '8000')
        );

        $this->assertSame('//snicco.test:8000/js/frontend.js', $factory('js/frontend.js'));
        $this->assertSame('//snicco.test:8000/js/frontend.js', $factory('/js/frontend.js'));

        $factory = new AssetFactory(
            '/foo/bar/public',
            $this->fixturesDir() . '/public/manifest.json',
            false,
            new HMRConfig('snicco.test', '8000', 'https')
        );

        $this->assertSame('https://snicco.test:8000/js/frontend.js', $factory('js/frontend.js'));
        $this->assertSame('https://snicco.test:8000/js/frontend.js', $factory('/js/frontend.js'));
    }

    /**
     * @test
     */
    public function that_missing_files_can_be_allowed(): void
    {
        $factory = new AssetFactory('/foo/bar/public', $this->fixturesDir() . '/public/manifest.json', true);

        $this->assertSame('/js/bogus.js', $factory('/js/bogus.js'));
    }

    private function fixturesDir(): string
    {
        return dirname(__DIR__) . '/fixtures';
    }
}
