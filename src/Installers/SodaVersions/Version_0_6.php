<?php

namespace Soda\Installer\Installers\SodaVersions;

class Version_0_6 extends Version_Default
{
    protected function getServiceProviderClassPath()
    {
        return 'Soda\\Cms\\SodaServiceProvider::class';
    }
}
