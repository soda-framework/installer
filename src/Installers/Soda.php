<?php

namespace Soda\Installer\Installers;

use Composer\Semver\VersionParser;
use Soda\Installer\SodaVersions\Version_Default;

class Soda extends AbstractInstaller
{
    public function run($version)
    {
        $versionedInstaller = $this->getVersionedInstaller($version);

        $this->output->writeln('<fg=cyan;>Pouring Soda...</>');
        $this->install($version);

        $this->output->writeln('<fg=cyan;>Caffeinating...</>');
        $versionedInstaller->addServiceProvider();
        $versionedInstaller->configure();

        $this->output->writeln('<fg=cyan;>Loading with sugar...</>');
        $versionedInstaller->migrate();
    }

    public function requiresMIKConfiguration()
    {
        return $this->input->getOption('mik') ? true : false;
    }

    protected function install($version)
    {
        $composer = $this->findComposer();

        $this->runCommands($this->formatCommands([
            "$composer require soda-framework/cms:$version",
        ]));
    }

    protected function getVersionedInstaller($version)
    {
        $className = $this->getVersionClassNamespace() . '\\' . $this->determineVersionedInstallerClassName($version);

        return new $className($this, $version);
    }

    protected function determineVersionedInstallerClassName($version)
    {
        $versionParse = new VersionParser;
        $requestedVersion = (new VersionParser)->parseConstraints($version);

        $versionNames = [
            'Version_Default' => [
                '^0.5',
                '^0.4',
                '^0.3',
                '^0.2',
                '^0.1',
                '^0.0',
            ],
            'Version_0_6'     => [
                '^0.6',
                '^0.7',
            ],
            'Version_0_8'     => [
                '^0.8',
                '^0.9',
            ],
        ];

        foreach ($versionNames as $name => $sodaConstraints) {
            foreach ($sodaConstraints as $sodaConstraint) {
                if ($versionParse->parseConstraints($sodaConstraint)->matches($requestedVersion)) {
                    return $name;
                }
            }
        }

        return $this->getLatestVersionClass();
    }

    protected function getVersionClassNamespace()
    {
        return '\\Soda\\Installer\\Installers\\SodaVersions';
    }

    protected function getLatestVersionClass()
    {
        return 'Version_0_10';
    }
}
