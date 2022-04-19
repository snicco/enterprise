<?php

declare(strict_types=1);

use Snicco\Component\StrArr\Str;
use PHP_CodeSniffer\Tokenizers\PHP;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

final class ConfigureCommand extends Command
{
    
    private string $repo_root_directory;
    private bool   $is_windows;
    private string $composer_json;
    
    public function __construct(string $repo_root_directory, string $composer_json)
    {
        $this->repo_root_directory = $repo_root_directory;
        $this->is_windows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
        $this->composer_json = $composer_json;
        parent::__construct('configure');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $symfony_style = new SymfonyStyle($input, $output);
        
        $output->write(PHP_EOL."<fg=cyan>
             _______..__   __.  __    ______   ______   ______
    /       ||  \ |  | |  |  /      | /      | /  __  \
   |   (----`|   \|  | |  | |  ,----'|  ,----'|  |  |  |
    \   \    |  . `  | |  | |  |     |  |     |  |  |  |
.----)   |   |  |\   | |  | |  `----.|  `----.|  `--'  |
|_______/    |__| \__| |__|  \______| \______| \______/
</>".PHP_EOL.PHP_EOL);
        
        $symfony_style->title('Starting interactive configuration of your new plugin.');
        
        // This will be the name of the directory passed to composer create-project
        $base_name = basename($this->repo_root_directory);
        
        $vendor_name = $this->slugify($this->askNonEmptyValue($symfony_style, 'Vendor slug', $base_name));
        $vendor_domain = $this->askNonEmptyValue($symfony_style, 'Vendor domain', "$vendor_name.com");
        $vendor_namespace =
            $this->askNonEmptyValue($symfony_style, 'Vendor namespace', ucwords($this->titleCase($vendor_name)));
        $vendor_title = $this->askNonEmptyValue($symfony_style, 'Vendor title (This is a human readable title. Used for example on the WP plugins page.', $this->titleCase($vendor_name));
        $vendor_caps = $this->askNonEmptyValue($symfony_style, 'Vendor caps', strtoupper($vendor_title));
        $vendor_text_domain = $this->askNonEmptyValue($symfony_style, 'Vendor textdomain', "$vendor_name");
        
        $symfony_style->section('The following placeholders will be replaced in all files:');
        $symfony_style->definitionList(
            ['VENDOR_SLUG' => $vendor_name],
            ['VENDOR_TITLE' => $vendor_title],
            ['VENDOR_CAPS' => $vendor_caps],
            ['VENDOR_NAMESPACE' => $vendor_namespace],
            ['VENDOR_DOMAIN' => $vendor_domain],
            ['VENDOR_TEXTDOMAIN' => $vendor_text_domain],
            ['PLUGIN_BASENAME' => $base_name]
        );
        
        if ($symfony_style->confirm(
            'This is a destructive process that can only be run once. Do you want to continue?'
        )) {
            $this->replaceFiles(
                $symfony_style,
                $vendor_name,
                $vendor_title,
                $vendor_caps,
                $vendor_namespace,
                $vendor_domain,
                $vendor_text_domain,
                $base_name,
            );
        } else {
            $symfony_style->warning('Aborting installation script. Nothing modified.');
            return Command::SUCCESS;
        }
        
        $symfony_style->section('Installing dependencies');
        $this->installDependencies($symfony_style);
        
        $symfony_style->section('Running cleanup');
        $this->cleanUp($symfony_style);
    
        $symfony_style->section("Copying src directory to namespace $vendor_namespace");
        $this->copySrcToVendorNamespace($symfony_style, $vendor_namespace );
    
        $symfony_style->section("Copying .env.testing.dist");
        $this->copyTestFiles($symfony_style);
        
        $symfony_style->section('Copying plugin contents into project root directory');
        $this->copyPluginContents($symfony_style);
        
        $symfony_style->success('You are all set. Now develop something awesome.');
        
        return Command::SUCCESS;
    }
    
    private function slugify(string $subject) :string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $subject), '-'));
    }
    
    private function titleCase(string $subject) :string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $subject)));
    }
    
    /**
     * @param  array<string,string>  $replacements
     */
    private function replaceInFile(SplFileInfo $file, array $replacements) :void
    {
        $file_path = $file->getRealPath();
        $file_basename = $file->getBasename();
        $contents = file_get_contents($file_path);
        
        Assert::string($contents, "Could not read contents of file {$file_path}.");
        
        $success = file_put_contents(
            $file_path,
            str_replace(
                array_keys($replacements),
                array_values($replacements),
                $contents
            )
        );
        Assert::notFalse($success, "Could not update contents of file $file_path.");
        
        foreach ($replacements as $placeholder => $value) {
            $file_basename = Str::replaceAll($file_basename, $placeholder, $value);
        }
        
        if ($file_basename === $file->getBasename()) {
            return;
        }
        
        $this->renameFile($file_path, dirname($file_path).DIRECTORY_SEPARATOR.$file_basename);
    }
    
    private function askNonEmptyValue(SymfonyStyle $output, string $question, ?string $default = null) :string
    {
        return $output->ask($question, $default, function ($value) {
            Assert::stringNotEmpty($value);
            return $value;
        });
    }
    
    /**
     * @return SplFileInfo[]
     */
    private function getAllFiles() :array
    {
        $finder = Finder::create()
                        ->in($this->repo_root_directory)
                        ->exclude(['vendor', 'setup', 'node_modules'])
                        ->notName([
                            'composer.lock',
                            '.gitignore',
                        ])
                        ->ignoreVCS(true)
                        ->ignoreDotFiles(false)
                        ->files()
                        ->sortByType()
                        ->depth('< 10');
        
        return iterator_to_array($finder);
    }
    
    private function replaceFiles(
        SymfonyStyle $symfony_style,
        string $vendor_name,
        string $vendor_title,
        string $vendor_caps,
        string $vendor_namespace,
        string $vendor_domain,
        string $vendor_textdomain,
        string $base_name
    ) :void {
        $files = $this->getAllFiles();
        
        $progress_bar = new ProgressBar($symfony_style, count($files));
        $progress_bar->setFormat('%current%/%max% [%bar%] %percent:3s%% -- %message% <info>(%filename%)</info>');
        
        $progress_bar->setMessage('Customizing your plugin...');
        $progress_bar->start();
        
        foreach ($files as $file) {
            $progress_bar->setMessage('Customizing your plugin...');
            
            $rel_path = str_replace($this->repo_root_directory, '', $file->getRealPath());
            
            $progress_bar->setMessage($rel_path, 'filename');
            $this->replaceInFile($file, [
                'VENDOR_SLUG' => $vendor_name,
                'VENDOR_TITLE' => $vendor_title,
                'VENDOR_CAPS' => $vendor_caps,
                'VENDOR_NAMESPACE' => $vendor_namespace,
                'VENDOR_DOMAIN' => $vendor_domain,
                'VENDOR_TEXTDOMAIN' => $vendor_textdomain,
                'PLUGIN_BASENAME' => $base_name
            ]);
            $progress_bar->advance();
        }
        $this->replaceInFile(new SplFileInfo($this->composer_json), ['vendor_slug/plugin' => "$vendor_name/plugin"]);
        
        $progress_bar->finish();
        
        $symfony_style->success('All files are now branded.');
    }
    
    private function cleanUp(SymfonyStyle $symfony_style) :void
    {
        if ($this->is_windows) {
            $symfony_style->warning('Skipping cleanup on windows.');
            return;
        }
        
        if ($symfony_style->confirm('Delete the .setup folder')) {
            $process = new Process(['rm', '-rf', $this->repo_root_directory.DIRECTORY_SEPARATOR.'.setup']);
            $process->mustRun();
            $symfony_style->success('./setup folder has been deleted.');
        }
        
        if ($symfony_style->confirm('Delete the symfony/console dependencies')) {
            $process = new Process(['rm', '-rf', $this->repo_root_directory.DIRECTORY_SEPARATOR.'vendor']);
            $process->mustRun();
            $symfony_style->success('symfony/console dependencies have been deleted.');
        }
    }
    
    private function installDependencies(SymfonyStyle $symfony_style) :void
    {
        if ($symfony_style->confirm('Do you want to install composer dependencies now?')) {
            $process = new Symfony\Component\Process\Process([
                'composer',
                'install',
            ], getcwd().DIRECTORY_SEPARATOR.'plugin');
            
            if ( ! $this->is_windows && file_exists('/dev/tty') && is_readable('/dev/tty')) {
                try {
                    $process->setTty(true);
                } catch (RuntimeException $e) {
                    $symfony_style->warning($e->getMessage());
                }
            }
            
            $process->run(function ($type, $line) use ($symfony_style) {
                $symfony_style->writeln($line);
            });
            
            if ($process->isSuccessful()) {
                $symfony_style->success('composer dependencies installed.');
            } else {
                $symfony_style->warning('composer dependencies could not be installed');
            }
        }
        
        if ($symfony_style->confirm('Do you want to install npm dependencies now?')) {
            $process = new Symfony\Component\Process\Process([
                'npm',
                'install',
            ], getcwd().DIRECTORY_SEPARATOR.'plugin');
            
            if ( ! $this->is_windows && file_exists('/dev/tty') && is_readable('/dev/tty')) {
                try {
                    $process->setTty(true);
                } catch (RuntimeException $e) {
                    $symfony_style->warning($e->getMessage());
                }
            }
    
            $process->run(function ($type, $line) use ($symfony_style) {
                $symfony_style->writeln($line);
            });
            
            if ($process->isSuccessful()) {
                $symfony_style->success('npm dependencies installed.');
            } else {
                $symfony_style->warning('npm dependencies could not be installed');
            }
        }
    }
    
    private function renameFile(string $from, string $to) :void
    {
        Assert::true(
            rename($from, $to),
            "Could not rename file $from"
        );
    }
    
    private function copyPluginContents(SymfonyStyle $symfony_style) :void
    {
        if ($this->is_windows) {
            $symfony_style->warning('Skipped copying of plugin contents on windows.');
            return;
        }
        
        $plugin_src_dir = $this->repo_root_directory.'/plugin';
        
        $command = sprintf('mv %s/* %s', $plugin_src_dir, $this->repo_root_directory);
        exec($command, $output);
        
        $command = sprintf('mv %s/.* %s', $plugin_src_dir, $this->repo_root_directory);
        exec($command, $output);
        
        rmdir($plugin_src_dir);
        
        $symfony_style->success('Copied plugin contents to project root directory.');
        
    }
    
    private function copySrcToVendorNamespace(SymfonyStyle $symfony_style, string $vendor_namespace) :void
    {
        if ($this->is_windows) {
            $symfony_style->warning('Skipped copying of src directory on windows.');
            return;
        }
    
        $command = sprintf('mv %s %s', 'plugin/src/VENDOR_NAMESPACE', 'plugin/src/'.$vendor_namespace);
        exec($command, $output);
    
        $symfony_style->success("Copied src contents to namespace $vendor_namespace.");
        
    }
    
    private function copyTestFiles(SymfonyStyle $symfony_style) :void
    {
        copy($this->repo_root_directory.'/plugin/tests/.env.testing.dist', $this->repo_root_directory.'/plugin/tests/.env.testing');
        $symfony_style->success('Copied .env.testing.dist to .env.testing');
    }
    
}