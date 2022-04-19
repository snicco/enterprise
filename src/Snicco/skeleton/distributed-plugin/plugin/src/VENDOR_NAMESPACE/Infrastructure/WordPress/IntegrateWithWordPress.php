<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Infrastructure\WordPress;

use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\Kernel\Bootstrapper;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use VENDOR_NAMESPACE\Infrastructure\WordPress\Translation\PHPFileTranslator;
use VENDOR_NAMESPACE\Infrastructure\WordPress\Translation\Translator;

use function add_action;
use function load_plugin_textdomain;
use function register_activation_hook;

use function register_deactivation_hook;

use function str_replace;

use const WP_PLUGIN_DIR;

final class IntegrateWithWordPress implements Bootstrapper
{
    private string $language_dir = '';

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
    }

    public function register(Kernel $kernel): void
    {
        $resource_dir = $kernel->config()
            ->getString('VENDOR_SLUG.resource_dir');
        $language_dir = $kernel->directories()
            ->baseDir() . "/{$resource_dir}/languages";

        $this->language_dir = $language_dir;

        $container = $kernel->container();
        $container->shared(Translator::class, function () {
            /**
             * @var array<string,string> $translations
             * @psalm-suppress UnresolvableInclude
             */
            $translations = require "{$this->language_dir}/domain-errors.php";

            return new PHPFileTranslator($translations);
        });
    }

    public function bootstrap(Kernel $kernel): void
    {
        $this->loadTextDomain();
        $this->activatePluginEvent($kernel);
        $this->deactivatePluginEvent($kernel);
    }

    private function loadTextDomain(): void
    {
        add_action('plugins_loaded', function () {
            $rel_path_to_language = str_replace(WP_PLUGIN_DIR, '', $this->language_dir);
            load_plugin_textdomain('VENDOR_TEXTDOMAIN', false, $rel_path_to_language);
        });
    }

    private function activatePluginEvent(Kernel $kernel): void
    {
        $file = $kernel->directories()
            ->baseDir() . '/PLUGIN_BASENAME.php';

        register_activation_hook($file, function () use ($kernel) {
            $activate = new ActivatePlugin(
                $kernel->container()[BetterWPDB::class],
                $kernel->config()
                    ->getString('ebooks.table')
            );
            $activate();
        });
    }

    private function deactivatePluginEvent(Kernel $kernel): void
    {
        $file = $kernel->directories()
            ->baseDir() . '/PLUGIN_BASENAME.php';

        register_deactivation_hook($file, function () {
            $deactivate = new DeactivatePlugin();
            $deactivate();
        });
    }
}
