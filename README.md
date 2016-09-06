# Soda Installer
A sweet utility for setting up Laravel + Soda applications quickly

## Installation
First, download the Soda installer using Composer:

`composer global require soda-framework/installer`

Make sure to place the `~/.composer/vendor/bin` directory (or the equivalent directory for your OS) in your `$PATH` so the soda executable can be located by your system.

## Usage
Once installed, the soda new command will create a fresh Laravel installation in the directory you specify. For instance, soda new blog will create a directory named blog containing a fresh Laravel installation with all of Laravel and Soda's dependencies already installed:

`soda new application-name`
