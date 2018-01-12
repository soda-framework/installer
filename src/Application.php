<?php

namespace Soda\Installer;

use Composer\Cache;
use Composer\IO\ConsoleIO;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends BaseApplication
{
    public function checkVersion(InputInterface $input, OutputInterface $output, $doCache = true)
    {
        $io = new ConsoleIO($input, $output, $this->getHelperSet());
        $cache = $doCache ? new Cache($io, sys_get_temp_dir().DIRECTORY_SEPARATOR.'soda-framework') : null;

        $versionChecker = new VersionChecker($io, $cache);
        $versionChecker->run($input, $output);
    }
}
