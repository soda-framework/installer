<?php

namespace Soda\Installer;

use Composer\DependencyResolver\Pool;
use Composer\Factory;
use Composer\IO\ConsoleIO;
use Composer\Json\JsonValidationException;
use Composer\Package\Package;
use Composer\Package\RootPackageInterface;
use Composer\Repository\CompositeRepository;
use Composer\Repository\RepositoryManager;
use Composer\Semver\Constraint\Constraint;
use Composer\Semver\Constraint\ConstraintInterface;
use Composer\Util\ErrorHandler;
use InvalidArgumentException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VersionChecker extends Application
{
    /** @var \Composer\Composer */
    private $composer;

    const PACKAGE_NAME = 'soda-framework/installer';

    /**
     * @param InputInterface $input  An Input instance
     * @param OutputInterface $output An Output instance
     *
     * @return int 0 if everything went fine, or an error code
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $io = new ConsoleIO($input, $output, $this->getHelperSet());
        ErrorHandler::register($io);
        $this->registerComposer($io);

        $this->checkVersions();

        die();

        return parent::doRun($input, $output);
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
        if ($this->composer === NULL) {
            try {
                $this->composer = Factory::createGlobal($io, FALSE);
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

    private function checkVersions()
    {
        foreach ($this->composer->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {
            if (static::PACKAGE_NAME === $package->getName()) {
                $installerPackage = $package;
            }
        }

        if(!isset($installerPackage)) {
            // Not installed globally;
            return;
        }


        $currentVersion = $installerPackage->getPrettyVersion();
        var_dump($currentVersion);
        $latestInstallerPackage = $this->getMostRecent($installerPackage->getName(), new Constraint('>', $installerPackage->getVersion()));
        var_dump($latestInstallerPackage);

        die();
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
                and ( ! $latest or version_compare($package->getVersion(), $latest->getVersion())==1)) {
                $latest = $package;
            }
        }

        return $latest;
    }
}
