<?php

namespace Soda\Installer\Installers\SodaVersions;

class Version_Default extends AbstractVersion
{
    /**
     * Configure the newly built Laravel application for Soda
     */
    public function configure()
    {
        $artisan = $this->installer->findArtisan();

        /*
        $baseCommands = [
            "$artisan vendor:publish",
            "$artisan optimize",
        ];

        if($this->installer->requiresMIKConfiguration()) {
            $baseCommands[] = "$artisan session:table";
        }
        */

        $commands = $this->installer->formatCommands([
            "$artisan vendor:publish",
            "$artisan optimize",
            "$artisan session:table"
        ]);

        $commands[] = "$artisan soda:setup";

        $this->installer->runCommands($commands);
    }

    /**
     * Run all necessary database setup
     */
    public function migrate()
    {
        $artisan = $this->installer->findArtisan();

        $commands = $this->installer->formatCommands([
            "$artisan migrate",
            "$artisan soda:migrate",
            "$artisan soda:seed",
        ]);

        $this->installer->runCommands($commands);
    }

    /**
     * Add the Soda CMS service provider to the Laravel app config file
     */
    public function addServiceProvider()
    {
        $applicationConfigFilePath = $this->installer->getWorkingDirectory() . '/config/app.php';

        if (file_exists($applicationConfigFilePath)) {
            $applicationConfigFileContents = file_get_contents($applicationConfigFilePath);

            $appServiceProvider = 'App\Providers\RouteServiceProvider::class,';
            $appendedServiceProvider = $appServiceProvider . PHP_EOL . PHP_EOL . str_repeat(' ', 8) . $this->getServiceProviderClassPath() . ',';

            file_put_contents($applicationConfigFilePath, str_replace($appServiceProvider, $appendedServiceProvider, $applicationConfigFileContents));
        }
    }

    /**
     * Get the default class path for the Soda CMS service provider
     *
     * @return string
     */
    protected function getServiceProviderClassPath()
    {
        return 'Soda\\Cms\\Providers\\SodaServiceProvider::class';
    }
}
