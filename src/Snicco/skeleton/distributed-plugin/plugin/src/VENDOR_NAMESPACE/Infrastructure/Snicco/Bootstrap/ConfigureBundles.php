<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Infrastructure\Snicco\Bootstrap;

use Nyholm\Psr7\Factory\Psr17Factory;
use Snicco\Component\Kernel\Bootstrapper;
use Snicco\Bundle\HttpRouting\Psr17FactoryDiscovery;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Component\Templating\Context\GlobalViewContext;
use Snicco\Enterprise\Component\Asset\AssetFactory;
use Snicco\Enterprise\Component\Asset\HMRConfig;
use VENDOR_NAMESPACE\Infrastructure\Snicco\Http\ErrorHandling\DomainExceptionTransformer;
use VENDOR_NAMESPACE\Infrastructure\WordPress\Translation\Translator;
use Webmozart\Assert\Assert;

use function file_get_contents;
use function is_file;

use function parse_url;
use function plugin_dir_url;
use function trim;

/**
 * This bootstrapper can be used to customize or overwrite service definitions
 * that are provided by third-party bundles.
 */
final class ConfigureBundles implements Bootstrapper
{
    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
    }

    public function register(Kernel $kernel): void
    {
        $container = $kernel->container();

        $container->shared(
            DomainExceptionTransformer::class,
            fn (): DomainExceptionTransformer => new DomainExceptionTransformer($container[Translator::class])
        );

        $container->shared(AssetFactory::class, function () use ($kernel) {
            $dist_dir_relative = $kernel->config()
                ->getString('VENDOR_SLUG.dist_dir');

            $dist_dir = $kernel->directories()
                ->baseDir() . "/{$dist_dir_relative}";

            if (is_file($dist_dir . '/hot')) {
                $url = trim((string) file_get_contents($dist_dir . '/hot'));

                $parts = parse_url($url);
                $host = $parts['host'] ?? '';
                $port = (string) ($parts['port'] ?? '');
                $scheme = $parts['scheme'] ?? '';

                Assert::stringNotEmpty($host);
                Assert::stringNotEmpty($port);
                Assert::stringNotEmpty($scheme);
                Assert::oneOf($scheme, ['http', 'https']);

                /** @var "https"|"http" $scheme */
                $hmr_config = new HMRConfig($host, $port, $scheme);
            } else {
                $hmr_config = null;
            }

            $plugin_dir = plugin_dir_url($kernel->directories()->baseDir() . '/PLUGIN_BASENAME.php');

            return new AssetFactory(
                $plugin_dir . $dist_dir_relative,
                $dist_dir . '/mix-manifest.json',
                $kernel->env()
                    ->isTesting(),
                $hmr_config
            );
        });
        
        $container->shared(Psr17FactoryDiscovery::class, function () {
            return new Psr17FactoryDiscovery([
                Psr17Factory::class => [
                    'server_request' => Psr17Factory::class,
                    'uri' => Psr17Factory::class,
                    'uploaded_file' => Psr17Factory::class,
                    'stream' => Psr17Factory::class,
                    'response' => Psr17Factory::class,
                ],
            ]);
        });
        
    }

    public function bootstrap(Kernel $kernel): void
    {
        $context = $kernel->container()
            ->make(GlobalViewContext::class);
        $context->add('asset', fn () => $kernel->container()->make(AssetFactory::class));
    }
}
