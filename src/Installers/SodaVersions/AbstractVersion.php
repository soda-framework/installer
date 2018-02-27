<?php

namespace Soda\Installer\Installers\SodaVersions;

use Soda\Installer\Installers\Soda;

abstract class AbstractVersion
{
    protected $installer;

    public function __construct(Soda $installer)
    {
        $this->installer = $installer;
    }

    abstract public function addServiceProvider();

    abstract public function configure();

    abstract public function migrate();
}
