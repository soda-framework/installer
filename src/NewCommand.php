<?php

namespace Soda\Installer;

use Composer\Semver\VersionParser;
use Soda\Installer\Installers\Laravel;
use Soda\Installer\Installers\Soda;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NewCommand extends Command
{
    protected $name;

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
            ->addArgument('name')
            ->addOption('show-output', null, InputOption::VALUE_NONE, 'Shows the output of composer/artisan commands during installation')
            ->addOption('release', 'r', InputOption::VALUE_OPTIONAL, 'Specify the version of Soda to install', '^0.9')
            ->addOption('mik', null, InputOption::VALUE_NONE, 'Additional setup for MIK deploys')
            ->addOption('no-cache', null, InputOption::VALUE_NONE, 'Don\'t cache the results.');
    }

    /**
     * Execute the command.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     *
     * @return void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<bg=blue;fg=cyan;>                    </>');
        $output->writeln('<bg=blue;fg=cyan;>   Soda Installer   </>');
        $output->writeln('<bg=blue;fg=cyan;>                    </>');

        $this->getApplication()->checkVersion($input, $output, $input->getOption('no-cache') ? false : true);
        $workingDirectory = $this->resolveWorkingDirectory($input);

        // Laravel Installation
        $laravelInstaller = new Laravel($workingDirectory, $input, $output);
        $laravelInstaller->verifyInstallation();
        $laravelVersion = $this->getLaravelVersion($input->getOption('release'));

        $output->writeln('<fg=cyan;>Installing Laravel (' . $laravelVersion . ')...</>');
        $laravelInstaller->run($laravelVersion);
        $output->writeln('<fg=cyan;>Laravel installed.</>');

        // Soda Installation
        $sodaVersionInstaller = new Soda($workingDirectory, $input, $output);
        $sodaVersionInstaller->run($input->getOption('release'));

        $output->writeln('<bg=blue;fg=cyan;>                                     </>');
        $output->writeln('<bg=blue;fg=cyan;>   Sweet! Soda has been installed!   </>');
        $output->writeln('<bg=blue;fg=cyan;>                                     </>');
    }

    /**
     * Get the version that should be downloaded.
     *
     * @param $version
     *
     * @return string
     */
    public function getLaravelVersion($version)
    {
        $versionParse = new VersionParser;
        $requestedVersion = (new VersionParser)->parseConstraints($version);

        $laravelVersions = [
            '5.4' => [
                '^0.9',
                '^0.8',
                '^0.7',
                '^0.6',
                'dev-release/0.9',
                'dev-release/0.8',
                'dev-release/0.7',
                'dev-release/0.6',
                'dev-master',
                'dev-develop',
            ],
            '5.3' => [
                '^0.5',
                '^0.4',
            ],
            '5.2' => [
                '^0.3',
                '^0.2',
                '^0.1',
                '^0.0',
            ],
        ];

        foreach ($laravelVersions as $laravelVersion => $sodaConstraints) {
            foreach ($sodaConstraints as $sodaConstraint) {
                if ($versionParse->parseConstraints($sodaConstraint)->matches($requestedVersion)) {
                    return $laravelVersion;
                }
            }
        }

        return '5.5';
    }

    private function resolveWorkingDirectory(InputInterface $input)
    {
        $name = $input->getArgument('name');

        return getcwd() . ($name ? '/' . $name : '') . ($input->getOption('mik') ? '/src' : '');
    }
}
