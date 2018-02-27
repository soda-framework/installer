<?php

namespace Soda\Installer\Installers\SodaVersions;

class Version_0_10 extends Version_0_8
{
    public function configure()
    {
        $artisan = $this->installer->findArtisan();

        $commands = $this->installer->formatCommands([
            "$artisan vendor:publish --provider=\"Soda\Cms\SodaServiceProvider\"",
            "$artisan session:table",
            "$artisan optimize",
            "$artisan key:generate",
        ]);

        $commands[] = "$artisan soda:setup";

        $this->installer->runCommands($commands);
    }
}
