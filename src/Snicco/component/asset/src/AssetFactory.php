<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Asset;

use InvalidArgumentException;
use RuntimeException;

use function file_get_contents;
use function is_file;
use function json_decode;
use function ltrim;
use function rtrim;

use function sprintf;
use function trim;
use const JSON_THROW_ON_ERROR;

/**
 * This class handles generating links to assets based on a manifest.json file.
 * It supports webpacks HMR replacement.
 */
final class AssetFactory
{
    private string $static_prefix;

    private string $manifest_file;

    private bool $allow_missing_assets;

    /**
     * @var array<string,string>|null
     */
    private ?array $manifest_contents = null;

    private ?HMRConfig $hmr_config;

    public function __construct(
        string $static_prefix,
        string $manifest_file,
        bool $allow_missing_assets = false,
        ?HMRConfig $hmr_config = null
    ) {
        if (! is_file($manifest_file)) {
            throw new InvalidArgumentException(sprintf('[%s] is not a valid manifest file.', $manifest_file));
        }

        $this->static_prefix = rtrim($static_prefix, '/');
        $this->manifest_file = $manifest_file;
        $this->hmr_config = $hmr_config;
        $this->allow_missing_assets = $allow_missing_assets;
    }

    public function __invoke(string $asset): string
    {
        $normalized_asset = '/' . ltrim($asset, '/');

        if (isset($this->hmr_config)) {
            return $this->hotModuleReplacementUrl($normalized_asset, $this->hmr_config);
        }

        $manifest = $this->manifestContents();

        $path = $manifest[$normalized_asset] ?? null;

        if (null === $path) {
            $path = $manifest[ltrim($normalized_asset, '/')] ?? null;
        }

        if (null === $path) {
            if ($this->allow_missing_assets) {
                return $asset;
            }

            throw MissingManifestAsset::forAsset($normalized_asset, $this->manifest_file);
        }

        return $this->static_prefix . '/' . trim($path, '/');
    }

    /**
     * @return array<string,string>
     */
    private function manifestContents(): array
    {
        if (! $this->manifest_contents) {
            $contents = file_get_contents($this->manifest_file);
            if (false === $contents) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException(sprintf('Could not read contents of file [%s]', $this->manifest_file));
                // @codeCoverageIgnoreEnd
            }

            /** @var array<string,string> $decoded */
            $decoded = json_decode($contents, true, JSON_THROW_ON_ERROR, JSON_THROW_ON_ERROR);
            $this->manifest_contents = $decoded;
        }

        return $this->manifest_contents;
    }

    private function hotModuleReplacementUrl(string $asset, HMRConfig $hmr_config): string
    {
        $url = '//' . $hmr_config->host() . ':' . $hmr_config->port() . $asset;
        if (null !== $hmr_config->scheme()) {
            $url = $hmr_config->scheme() . ':' . $url;
        }

        return $url;
    }
}
