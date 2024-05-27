<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Bridge\Standalone;

use Composer\Pcre\Preg;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Seld\PharUtils\Timestamps;

/**
 * The Compiler class compiles composer into a phar.
 *
 * Heavily inspired from composer code, all credits to their authors.
 *
 * @see https://github.com/composer/composer/blob/main/src/Composer/Compiler.php
 * @see https://getcomposer.org/
 */
class PharCompiler
{
    private \DateTime $versionDate;

    /**
     * Creates the PHAR.
     *
     * @param ?string $pharFile
     *   Full target PHAR file name.
     */
    public function compile(?string $pharFile = null): void
    {
        $pharFile ??= \dirname(__DIR__, 3) . '/db-tools.phar';

        if (\file_exists($pharFile)) {
            \unlink($pharFile);
        }

        $rootDir = \dirname(__DIR__, 3);

        // Next line would fetch the current reference (commit hash or tag).
        // $process = new Process(['git', 'log', '--pretty=%H', '-n1', 'HEAD'], $rootDir);

        $process = new Process(['git', 'log', '-n1', '--pretty=%ci', 'HEAD'], $rootDir);
        if ($process->run() !== 0) {
            throw new \RuntimeException('Can\'t run git log. You must ensure to run compile from composer git repository clone and that git binary is available.');
        }

        $this->versionDate = new \DateTime(\trim($process->getOutput()));
        $this->versionDate->setTimezone(new \DateTimeZone('UTC'));

        $phar = new \Phar($pharFile, 0, 'db-tools.phar');
        $phar->setSignatureAlgorithm(\Phar::SHA512);

        $phar->startBuffering();

        $finderSort = static fn ($a, $b): int => \strcmp(\strtr($a->getRealPath(), '\\', '/'), \strtr($b->getRealPath(), '\\', '/'));

        // Local package sources.
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->notName('Compiler.php')
            ->notName('ClassLoader.php')
            ->notName('InstalledVersions.php')
            ->in($rootDir.'/src')
            ->sort($finderSort)
        ;
        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }
        // Add runtime utilities separately to make sure they retains the docblocks as these will get copied into projects.
        $this->addFile($phar, new \SplFileInfo($rootDir . '/vendor/composer/ClassLoader.php'), false);
        $this->addFile($phar, new \SplFileInfo($rootDir . '/vendor/composer/InstalledVersions.php'), false);

        // Add vendor files
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->notPath('/\/(composer\.(json|lock)|[A-Z]+\.md(?:own)?|\.gitignore|appveyor.yml|phpunit\.xml\.dist|phpstan\.neon\.dist|phpstan-config\.neon|phpstan-baseline\.neon)$/')
            ->notPath('/(.*\.(md|xml|twig|svg)|Dockerfile|phpbench\.json|yaml-lint|dev\.sh|docker-compose\.(yaml|yml)|run-tests\.sh)/')
            ->notPath('/bin\/(jsonlint|validate-json|simple-phpunit|phpstan|phpstan\.phar)(\.bat)?$/')
            ->notPath('justinrainbow/json-schema/demo/')
            ->notPath('justinrainbow/json-schema/dist/')
            ->notPath('composer/LICENSE')
            ->exclude('Tests')
            ->exclude('tests')
            ->exclude('docs')
            ->in($rootDir.'/vendor/')
            ->sort($finderSort)
        ;

        $extraFiles = [];
        foreach ([
            $rootDir . '/vendor/composer/installed.json',
            // CaBundle::getBundledCaBundlePath(),
            $rootDir . '/vendor/composer/installed.json',
            $rootDir . '/vendor/symfony/console/Resources/bin/hiddeninput.exe',
            $rootDir . '/vendor/symfony/console/Resources/completion.bash',
            $rootDir . '/vendor/symfony/console/Resources/completion.fish',
            $rootDir . '/vendor/symfony/console/Resources/completion.zsh',
            $rootDir . '/vendor/composer/installed.json',
        ] as $file) {
            $extraFiles[$file] = \realpath($file);
            if (!\file_exists($file)) {
                throw new \RuntimeException('Extra file listed is missing from the filesystem: '.$file);
            }
        }
        $unexpectedFiles = [];

        foreach ($finder as $file) {
            if (false !== ($index = \array_search($file->getRealPath(), $extraFiles, true))) {
                unset($extraFiles[$index]);
            } elseif (!Preg::isMatch('{(^LICENSE$|\.php$)}', $file->getFilename())) {
                $unexpectedFiles[] = (string) $file;
            }

            if (Preg::isMatch('{\.php[\d.]*$}', $file->getFilename())) {
                $this->addFile($phar, $file);
            } else {
                $this->addFile($phar, $file, false);
            }
        }

        if (\count($extraFiles) > 0) {
            throw new \RuntimeException('These files were expected but not added to the phar, they might be excluded or gone from the source package:'.PHP_EOL.var_export($extraFiles, true));
        }
        if (\count($unexpectedFiles) > 0) {
            throw new \RuntimeException('These files were unexpectedly added to the phar, make sure they are excluded or listed in $extraFiles:'.PHP_EOL.var_export($unexpectedFiles, true));
        }

        // Add binary.
        $phar->addFile($rootDir . '/bin/db-tools.php', 'bin/db-tools.php');
        $content = \file_get_contents($rootDir.'/bin/db-tools');
        $content = Preg::replace('{^#!/usr/bin/env php\s*}', '', $content);
        $phar->addFromString('bin/db-tools', $content);

        // Stubs
        $phar->setStub(
            <<<'EOT'
            #!/usr/bin/env php
            <?php
            if (!\class_exists('Phar')) {
                echo 'PHP\'s phar extension is missing. DbTools requires it to run. Enable the extension or recompile php without --disable-phar then try again.' . PHP_EOL;
                exit(1);
            }
            Phar::mapPhar('db-tools.phar');
            require 'phar://db-tools.phar/bin/db-tools';
            __HALT_COMPILER();
            EOT,
        );

        $phar->stopBuffering();

        //$this->addFile($phar, new \SplFileInfo($rootDir.'/LICENSE.md'), false);

        unset($phar);

        // re-sign the phar with reproducible timestamp / signature
        $util = new Timestamps($pharFile);
        $util->updateTimestamps($this->versionDate);
        $util->save($pharFile, \Phar::SHA512);
    }

    private function getRelativeFilePath(\SplFileInfo $file): string
    {
        $rootDir = \dirname(__DIR__, 3);

        $realPath = $file->getRealPath();
        $pathPrefix = $rootDir . DIRECTORY_SEPARATOR;
        $pos = \strpos($realPath, $pathPrefix);
        $relativePath = ($pos !== false) ? \substr_replace($realPath, '', $pos, \strlen($pathPrefix)) : $realPath;

        return \strtr($relativePath, '\\', '/');
    }

    private function addFile(\Phar $phar, \SplFileInfo $file, bool $strip = true): void
    {
        $phar->addFile($this->getRelativeFilePath($file));
    }
}
