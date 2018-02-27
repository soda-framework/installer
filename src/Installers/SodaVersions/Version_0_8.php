<?php

namespace Soda\Installer\Installers\SodaVersions;

class Version_0_8 extends Version_0_6
{
    public function migrate()
    {
        $artisan = $this->installer->findArtisan();

        $commands = $this->installer->formatCommands([
            "$artisan migrate",
            "$artisan soda:install",
        ]);

        $this->installer->runCommands($commands);
    }
}
