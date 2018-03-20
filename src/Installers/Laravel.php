<?php

namespace Soda\Installer\Installers;

use GuzzleHttp\Client;
use RuntimeException;
use ZipArchive;

class Laravel extends AbstractInstaller
{
    public function verifyInstallation()
    {
        if (! class_exists('ZipArchive')) {
            throw new RuntimeException('The Zip PHP extension is not installed. Please install it and try again.');
        }

        $this->verifyApplicationDoesntExist();
    }

    public function run($version)
    {
        $this->download($zipFile = $this->makeFilename(), $version)
            ->extract($zipFile)
            ->cleanUp($zipFile);

        $composer = $this->findComposer();

        switch ($version) {
            case '5.0':
            case '5.1':
            case '5.2':
            case '5.3':
            case '5.4':
                $commands = [
                    $composer . ' install --no-scripts --no-suggest ',
                    $composer . ' run-script post-root-package-install ',
                    $composer . ' run-script post-install-cmd',
                    $composer . ' run-script post-create-project-cmd',
                ];
                break;
            default:
                $commands = [
                    $composer.' install --no-scripts',
                    $composer.' run-script post-root-package-install',
                    $composer.' run-script post-create-project-cmd',
                    $composer.' run-script post-autoload-dump',
                ];
                break;
        }

        $this->runCommands($this->formatCommands($commands));
    }

    /**
     * Verify that the application does not already exist.
     */
    protected function verifyApplicationDoesntExist()
    {
        if ((is_dir($this->workingDirectory) || is_file($this->workingDirectory)) && $this->workingDirectory != getcwd()) {
            throw new RuntimeException('Application already exists!');
        }
    }

    /**
     * Clean-up the Zip file.
     *
     * @param  string $zipFile
     *
     * @return $this
     */
    protected function cleanUp($zipFile)
    {
        @chmod($zipFile, 0777);

        @unlink($zipFile);

        return $this;
    }

    /**
     * Generate a random temporary filename.
     *
     * @return string
     */
    protected function makeFilename()
    {
        return getcwd() . '/laravel_' . md5(time() . uniqid()) . '.zip';
    }

    /**
     * Download the temporary Zip to the given file.
     *
     * @param  string $zipFile
     * @param  string $version
     *
     * @return $this
     */
    protected function download($zipFile, $version = 'master')
    {
        switch ($version) {
            case '5.2':
                $filename = 'v5.2.31.zip';
                break;
            case '5.3':
                $filename = 'v5.3.30.zip';
                break;
            case '5.4':
                $filename = 'v5.4.30.zip';
                break;
            default:
                $filename = 'v5.5.0.zip';
                break;
        }

        $response = (new Client)->get('https://github.com/laravel/laravel/archive/' . $filename);

        file_put_contents($zipFile, $response->getBody());

        return $this;
    }

    /**
     * Extract the zip file into the given directory.
     *
     * @param  string $zipFile
     *
     * @return $this
     */
    protected function extract($zipFile)
    {
        $archive = new ZipArchive;

        $archive->open($zipFile);

        $dirName = $archive->statIndex(0)['name'];

        $archive->extractTo(getcwd());

        $archive->close();

        if (! is_dir(dirname($this->workingDirectory))) {
            mkdir(dirname($this->workingDirectory), 0777, true);
        }

        rename(getcwd() . '/' . $dirName, $this->workingDirectory);

        return $this;
    }
}
