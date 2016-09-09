<?php

namespace Soda\Installer;

use Laravel\Installer\Console\NewCommand;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class InstallSoda extends NewCommand {
    protected $name;
    protected $directory;

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure() {
        $this
            ->setName('new')
            ->setDescription('Create a new Soda application.')
            ->addArgument('name')
            ->addOption('show-output', null, InputOption::VALUE_NONE, 'Shows the output of composer/artisan commands during installation')
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Installs the latest "development" release')
            ->addOption('master', null, InputOption::VALUE_NONE, 'Installs the "master" release')
            ->addOption('laravel-dev', null, InputOption::VALUE_NONE, 'Installs the latest "development" release of Laravel')
            ->addOption('laravel-master', null, InputOption::VALUE_NONE, 'Installs the "master" release of Laravel');
    }

    /**
     * Execute the command.
     *
     * @param  InputInterface $input
     * @param  OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->name = $input->getArgument('name');
        $this->directory = getcwd() . '/' . $this->name;

        $output->writeln('<bg=blue;fg=cyan;>                    </>');
        $output->writeln('<bg=blue;fg=cyan;>   Soda Installer   </>');
        $output->writeln('<bg=blue;fg=cyan;>                    </>');

        $this->installLaravel($input, $output);
        $this->installSoda($input, $output);
        $this->configureSoda($input, $output);
        $this->migrateSoda($input, $output);

        $output->writeln('<bg=blue;fg=cyan;>                                     </>');
        $output->writeln('<bg=blue;fg=cyan;>   Sweet! Soda has been installed!   </>');
        $output->writeln('<bg=blue;fg=cyan;>                                     </>');
    }

    protected function installLaravel(InputInterface $input, OutputInterface $output) {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('The Zip PHP extension is not installed. Please install it and try again.');
        }

        $this->verifyApplicationDoesntExist(
            $directory = ($this->name) ? getcwd() . '/' . $this->name : getcwd(),
            $output
        );

        $version = $this->getLaravelVersion($input);

        $output->writeln('<fg=cyan;>Installing Laravel (' . $version . ')...</>');

        $this->download($zipFile = $this->makeFilename(), $version)
            ->extract($zipFile, $directory)
            ->cleanUp($zipFile);

        $composer = $this->findComposer();

        $commands = $this->formatCommands($input, [
            $composer . ' install --no-scripts --no-suggest ',
            $composer . ' run-script post-root-package-install ',
            $composer . ' run-script post-install-cmd',
            $composer . ' run-script post-create-project-cmd',
        ]);

        $process = $this->buildProcess($commands, $directory);

        $process->run(function ($type, $line) use ($output) {
            $output->write($line);
        });

        $output->writeln('<fg=cyan;>Laravel installed.</>');
    }

    protected function installSoda(InputInterface $input, OutputInterface $output) {
        $composer = $this->findComposer();

        $output->writeln('<fg=cyan;>Pouring Soda...</>');

        $version = $this->getSodaVersion($input);

        $commands = $this->formatCommands($input, [
            "$composer require soda-framework/cms$version",
        ]);

        $process = $this->buildProcess($commands, $this->directory);

        $process->run(function ($type, $line) use ($output) {
            $output->write($line);
        });
    }

    protected function configureSoda(InputInterface $input, OutputInterface $output) {
        $directory = $this->directory;
        $artisan = $this->findArtisan($directory);

        $output->writeln('<fg=cyan;>Caffeinating...</>');

        $this->addServiceProvider();

        $commands = $this->formatCommands($input, [
            "$artisan vendor:publish",
            "$artisan session:table",
            "$artisan optimize",
        ]);

        $commands[] = "$artisan soda:setup";

        $process = $this->buildProcess($commands, $directory);

        $process->run(function ($type, $line) use ($output) {
            $output->write($line);
        });
    }

    protected function migrateSoda(InputInterface $input, OutputInterface $output) {
        $directory = $this->directory;
        $artisan = $this->findArtisan($directory);

        $output->writeln('<fg=cyan;>Loading with sugar...</>');

        $commands = $this->formatCommands($input, [
            "$artisan migrate",
            "$artisan soda:migrate",
            "$artisan soda:seed",
        ]);

        $process = $this->buildProcess($commands, $directory);

        $process->run(function ($type, $line) use ($output) {
            $output->write($line);
        });
    }

    protected function addServiceProvider() {
        $application_config = $this->directory . '/config/app.php';

        if (file_exists($application_config)) {
            $contents = file_get_contents($application_config);

            $old_provider = 'App\Providers\RouteServiceProvider::class,';
            $provider_replacement = "$old_provider\n\n        Soda\\Cms\\Providers\\SodaServiceProvider::class,";

            $old_facade = "'View' => Illuminate\\Support\\Facades\\View::class,";
            $facade_replacement = "$old_facade\n\n        'Soda' => Soda\\Facades\\Soda::class,";

            $contents = str_replace($old_provider, $provider_replacement, $contents);
            $contents = str_replace($old_facade, $facade_replacement, $contents);

            file_put_contents($application_config, $contents);
        }
    }

    protected function formatCommands(InputInterface $input, array $commands) {
        if ($input->getOption('no-ansi')) {
            $commands = array_map(function ($value) {
                return $value . ' --no-ansi';
            }, $commands);
        }

        if (!$input->getOption('show-output')) {
            $commands = array_map(function ($value) {
                return $value . ' --quiet';
            }, $commands);
        }

        if ($input->getOption('verbose')) {
            $commands = array_map(function ($value) {
                return $value . ' --verbose';
            }, $commands);
        }

        return $commands;
    }

    protected function buildProcess(array $commands, $directory) {
        $process = new Process(implode(' && ', $commands), $directory, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $process->setTty(true);
        }

        return $process;
    }


    /**
     * Get the version that should be downloaded.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface $input
     *
     * @return string
     */
    protected function getLaravelVersion($input) {
        if ($input->getOption('laravel-dev')) {
            return 'develop';
        }

        if ($input->getOption('laravel-master')) {
            return 'master';
        }

        return '5.2';
    }

    protected function getSodaVersion($input) {
        if ($input->getOption('dev')) {
            return ':dev-master';
        }

        return '';
    }

    /**
     * Get the artisan command for the environment.
     *
     * @return string
     */
    protected function findArtisan($directory = null) {
        if (file_exists(($directory ? $directory : getcwd()) . '/artisan')) {
            return '"' . PHP_BINARY . '" artisan';
        }

        return 'php artisan';
    }
}
