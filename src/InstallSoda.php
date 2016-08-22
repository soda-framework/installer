<?php

namespace Soda\Installer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallSoda extends Command
{
    protected $name;
    protected $directory;

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('new')
            ->setDescription('Create a new Soda application.')
            ->addArgument('name');
    }

    /**
     * Execute the command.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface  $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->name = $input->getArgument('name');
        $this->directory = getcwd() . '/' . $this->name;

        $output->writeln('<info>Installing Soda...</info>');

        $this->installLaravel($output);
        $this->installSoda($output);
        $this->addServiceProvider();
        $this->finishSetup($output);

        $output->writeln('<comment>Sweet! Soda has been installed</comment>');
    }

    protected function installLaravel(OutputInterface $output) {
        $name = $this->name;
        $directory = $this->directory;
        $composer = $this->findComposer($directory);

        $process = new Process(implode(' && ', [
            "$composer create-project laravel/laravel $name 5.2.*",
        ]), getcwd(), null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $process->setTty(true);
        }

        $process->run(function ($type, $line) use ($output) {
            $output->write($line);
        });
    }

    protected function installSoda(OutputInterface $output) {
        $directory = $this->directory;
        $composer = $this->findComposer();

        $process = new Process(implode(' && ', [
            "$composer require soda-framework/cms:dev-master",
        ]), $directory, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $process->setTty(true);
        }

        $process->run(function ($type, $line) use ($output) {
            $output->write($line);
        });
    }

    protected function finishSetup(OutputInterface $output) {
        $directory = $this->directory;
        $artisan = $this->findArtisan($directory);

        $process = new Process(implode(' && ', [
            "$artisan vendor:publish",
            "$artisan soda:setup",
        ]), $directory, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $process->setTty(true);
        }

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

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer($directory = null)
    {
        if (file_exists(($directory ? $directory : getcwd()).'/composer.phar')) {
            return '"'.PHP_BINARY.'" composer.phar';
        }

        return 'composer';
    }

    /**
     * Get the artisan command for the environment.
     *
     * @return string
     */
    protected function findArtisan($directory = null)
    {
        if (file_exists(($directory ? $directory : getcwd()).'/artisan')) {
            return '"'.PHP_BINARY.'" artisan';
        }

        return 'php artisan';
    }
}
