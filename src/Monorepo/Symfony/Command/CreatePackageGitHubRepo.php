<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Monorepo\Symfony\Command;

use InvalidArgumentException;
use RuntimeException;
use Snicco\Enterprise\Monorepo\Package\Package;
use Snicco\Enterprise\Monorepo\Package\PackageRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Webmozart\Assert\Assert;

use function curl_close;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt_array;
use function json_decode;
use function json_encode;
use function realpath;
use function sprintf;

use const CURLINFO_HTTP_CODE;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_POST;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_URL;
use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;

final class CreatePackageGitHubRepo extends Command
{
    private PackageRepository $package_repo;

    public function __construct(PackageRepository $package_repo)
    {
        parent::__construct('create-github-repo');
        $this->package_repo = $package_repo;
        $this->addArgument('package-dir', InputArgument::REQUIRED, 'The relative path to the package directory.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $package_dir = (string) realpath((string) $input->getArgument('package-dir'));

        $package = $this->package_repo->get($package_dir . DIRECTORY_SEPARATOR . 'composer.json');
        $api_token = $this->getApiToken();

        $style->title(sprintf('Creating GitHub repo for package %s', $package->name));

        $is_repo = $this->isRepo($api_token, $package);

        if ($is_repo) {
            $style->success(sprintf('%s is already on GitHub.', $package->name));

            return Command::SUCCESS;
        }

        $style->note(sprintf('%s is not on GitHub.', $package->name));

        $style->note('Creating GitHub repo now...');

        $repo_url = $this->createRepo($api_token, $package);

        $style->success(sprintf('Created new GitHub repo at %s', $repo_url));

        return Command::SUCCESS;
    }

    private function getApiToken(): string
    {
        if (! isset($_SERVER['CREATE_REPO_TOKEN'])) {
            throw new InvalidArgumentException('$_SERVER["CREATE_REPO_TOKEN"] is not set.');
        }

        $token = (string) $_SERVER['CREATE_REPO_TOKEN'];

        if ('' === $token) {
            throw new InvalidArgumentException('$_SERVER["CREATE_REPO_TOKEN"] can not be empty string.');
        }

        return $token;
    }

    private function isRepo(string $api_token, Package $package): bool
    {
        $handle = curl_init();
        Assert::notFalse($handle, 'Could not create curl handle.');

        $url = sprintf('https://api.github.com/repos/%s', $package->name);

        curl_setopt_array($handle, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                sprintf('Authorization: token %s', $api_token),
                'Accept: application/vnd.github.v3+json',
                'User-Agent: https://github.com/snicco-bot',
            ],
        ]);

        $output = curl_exec($handle);
        Assert::string($output, 'curl call did not return string.');

        $http_code = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);

        curl_close($handle);

        if (200 === $http_code) {
            return true;
        }

        if (404 === $http_code) {
            return false;
        }

        throw new RuntimeException($output);
    }

    private function createRepo(string $api_token, Package $package): string
    {
        $handle = curl_init();
        Assert::notFalse($handle, 'Could not create curl handle.');

        $url = sprintf('https://api.github.com/orgs/%s/repos', $package->vendor_name);

        $payload = json_encode([
            'org' => $package->vendor_name,
            'name' => $package->short_name,
            'description' => sprintf(
                '[READ ONLY] Subtree split of the %s package (see snicco/enterprise).',
                $package->name
            ),
            'homepage' => 'https://snicco.io',
            'private' => true,
            'has_issues' => false,
            'has_projects' => false,
            'has_wiki' => false,
            'auto_init' => true,
        ], JSON_THROW_ON_ERROR);

        curl_setopt_array($handle, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                sprintf('Authorization: token %s', $api_token),
                'Accept: application/vnd.github.v3+json',
                'User-Agent: https://github.com/snicco-bot',
                'Content-Type: application/json',
            ],
        ]);

        $output = curl_exec($handle);
        Assert::string($output, 'curl call did not return string.');

        $http_code = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);

        if (201 !== $http_code) {
            throw new RuntimeException("GitHub repo not created.\nHTTP-Code: {$http_code}.\n{$output}.");
        }

        $output_array = (array) json_decode($output, true, JSON_THROW_ON_ERROR, JSON_THROW_ON_ERROR);

        if (! isset($output_array['html_url'])) {
            throw new RuntimeException("GitHub response does not contain html_url.\n{$output}");
        }

        return (string) $output_array['html_url'];
    }
}
