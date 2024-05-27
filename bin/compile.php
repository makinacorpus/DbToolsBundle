<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle;

use MakinaCorpus\DbToolsBundle\Bridge\Standalone\PharCompiler;

/**
 * Please run before running this:
 *   $ composer config autoloader-suffix DbToolsPhar
 *   $ composer install --no-dev
 *   $ composer config autoloader-suffix --unset
 *   $ php -d phar.readonly=0 bin/compile.php
 */

(static function (): void {
    $autoloadFiles = [
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/../../../autoload.php',
    ];

    $autoloaderFound = false;
    foreach ($autoloadFiles as $autoloadFile) {
        if (!\file_exists($autoloadFile)) {
            continue;
        }
        require_once $autoloadFile;
        $autoloaderFound = true;
    }

    if (!$autoloaderFound) {
        if (\extension_loaded('phar') && \Phar::running() !== '') {
            \fwrite(STDERR, 'The PHAR was built without dependencies!' . \PHP_EOL);
            exit(1);
        }
        \fwrite(STDERR, 'vendor/autoload.php could not be found. Did you run `composer install`?' . \PHP_EOL);
        exit(1);
    }

    $cwd = \getcwd();
    \assert(\is_string($cwd));
    \chdir(__DIR__.'/../');
    $ts = \rtrim(\exec('git log -n1 --pretty=%ct HEAD'));
    if (!\is_numeric($ts)) {
        echo 'Could not detect date using "git log -n1 --pretty=%ct HEAD"'.\PHP_EOL;
        exit(1);
    }
    \chdir($cwd);

    \error_reporting(-1);
    \ini_set('display_errors', '1');

    $compiler = new PharCompiler();
    $compiler->compile();
    exit(1);
})();
