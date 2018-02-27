<?php

namespace Soda\Installer\Installers;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

abstract class AbstractInstaller
{
    protected $workingDirectory;
    protected $input;
    protected $output;

    public function __construct($workingDirectory, InputInterface $input, OutputInterface $output)
    {
        $this->workingDirectory = $workingDirectory;
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    public function findComposer()
    {
        if (file_exists(getcwd() . '/composer.phar')) {
            return '"' . PHP_BINARY . '" composer.phar';
        }

        return 'composer';
    }

    /**
     * Get the artisan command for the environment.
     *
     * @return string
     */
    public function findArtisan()
    {
        if (file_exists(($this->workingDirectory ?: getcwd()) . '/artisan')) {
            return '"' . PHP_BINARY . '" artisan';
        }

        return 'php artisan';
    }

    public function formatCommands(array $commands)
    {
        if ($this->input->getOption('no-ansi')) {
            $commands = array_map(function ($value) {
                return $value . ' --no-ansi';
            }, $commands);
        }

        if (! $this->input->getOption('show-output')) {
            $commands = array_map(function ($value) {
                return $value . ' --quiet';
            }, $commands);
        }

        if ($this->input->getOption('verbose')) {
            $commands = array_map(function ($value) {
                return $value . ' --verbose';
            }, $commands);
        }

        return $commands;
    }

    public function runCommands($commands)
    {
        $process = $this->buildProcess($commands, $this->getWorkingDirectory());

        $process->run(function ($type, $line) {
            $this->output->write($line);
        });
    }

    public function buildProcess(array $commands)
    {
        $process = new Process(implode(' && ', $commands), $this->getWorkingDirectory(), null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $process->setTty(true);
        }

        return $process;
    }

    /**
     * @return mixed
     */
    public function getWorkingDirectory()
    {
        return $this->workingDirectory;
    }
}
