<?php

namespace Soda\Installer;

use Composer\Cache;
use Composer\DependencyResolver\Pool;
use Composer\Factory;
use Composer\IO\ConsoleIO;
use Composer\Json\JsonValidationException;
use Composer\Package\Package;
use Composer\Repository\CompositeRepository;
use Composer\Semver\Constraint\Constraint;
use Composer\Semver\Constraint\ConstraintInterface;
use Composer\Util\ErrorHandler;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VersionChecker
{
    /** @var \Composer\Composer */
    private $composer;
    private $cache;

    const PACKAGE_NAME = 'soda-framework/installer';

    public function __construct(ConsoleIO $io, Cache $cache = null)
    {
        ErrorHandler::register($io);
        $this->registerComposer($io);
        $this->cache = $cache;

        if ($cache) {
            $cache->gc(86400, 1024);
        }
    }

    /**
     * @param InputInterface  $input  An Input instance
     * @param OutputInterface $output An Output instance
     *
     * @return int 0 if everything went fine, or an error code
     * @throws \ErrorException
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->composer->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {
            if (static::PACKAGE_NAME === $package->getName()) {
                $installerPackage = $package;
            }
        }

        if (! isset($installerPackage)) {
            $output->writeln('\n');
            $output->writeln('<bg=red;fg=white;>Soda Installer not detected in global composer.json.</>');
            $output->writeLn('<bg=red;fg=white;>Make sure you install with </><bg=red;fg=white;options=underscore>composer global require ' . static::PACKAGE_NAME . '</>');

            return;
        }

        $currentVersion = $installerPackage->getPrettyVersion();
        $latestVersion = $this->cache ? $this->cache->read('version') : false;

        if (! $latestVersion) {
            if ($latestInstallerPackage = $this->getMostRecent($installerPackage->getName(), new Constraint('>', $installerPackage->getVersion()))) {
                $latestVersion = $latestInstallerPackage->getPrettyVersion();

                if ($this->cache) {
                    $this->cache->write('version', $latestVersion);
                }
            }
        }

        if (version_compare($latestVersion, $currentVersion) == 1) {
            $output->writeLn('<bg=red;fg=white;>Your version of Soda Installer is out of date!</>');
            $output->writeLn('<bg=red;fg=white;>Please update with </><bg=red;fg=white;options=underscore>composer global update ' . static::PACKAGE_NAME . '</>');
            $output->writeLn('Your version: <options=underscore>' . $currentVersion . '</>');
            $output->writeLn('Current version: <options=underscore>' . $latestVersion . '</>');
        }

        return;
    }

    /**
     * @param ConsoleIO $io
     *
     * @return \Composer\Composer
     *
     * @throws JsonValidationException
     */
    public function registerComposer(ConsoleIO $io)
    {
        if ($this->composer === null) {
            try {
                $this->composer = Factory::createGlobal($io, false);
            } catch (InvalidArgumentException $e) {
                $io->writeError($e->getMessage());
                exit(1);
            } catch (JsonValidationException $e) {
                $errors = ' - ' . implode(PHP_EOL . ' - ', $e->getErrors());
                $message = $e->getMessage() . ':' . PHP_EOL . $errors;
                throw new JsonValidationException($message);
            }
        }

        return $this->composer;
    }

    /**
     * @return \Composer\DependencyResolver\Pool
     */
    private function getPool()
    {
        $pool = new Pool(
            $this->composer->getPackage()->getMinimumStability(),
            $this->composer->getPackage()->getStabilityFlags()
        );

        $pool->addRepository(new CompositeRepository($this->composer->getRepositoryManager()->getRepositories()));

        return $pool;
    }

    /**
     * Searches $packages for the most recent package matching $package_name.
     *
     * @access   protected
     *
     * @param                     $packageName
     * @param ConstraintInterface $constraint
     *
     * @return Package|false
     */
    protected function getMostRecent($packageName, ConstraintInterface $constraint)
    {
        $pool = $this->getPool();
        $latest = false;

        foreach ($pool->whatProvides($packageName, $constraint) as $package) {
            if (($package->getName() == $packageName)
                and (! $latest or version_compare($package->getVersion(), $latest->getVersion()) == 1)) {
                $latest = $package;
            }
        }

        return $latest;
    }
}
