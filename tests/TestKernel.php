<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use MakinaCorpus\DbToolsBundle\DbToolsBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    private string $testRootDir;

    public function __construct(string $environment, bool $debug)
    {
        $this->testRootDir = sys_get_temp_dir().'/'.uniqid('db_tools_', true);
        $filesystem = new Filesystem();

        $filesystem->mkdir($this->testRootDir . '/vendor');
        $filesystem->mkdir($this->testRootDir . '/var/cache');

        parent::__construct($environment, $debug);
    }

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new DoctrineBundle(),
            new DbToolsBundle(),
        ];
    }

    protected function configureContainer(ContainerBuilder $containerBuilder, LoaderInterface $loader)
    {
        $containerBuilder->loadFromExtension('framework', [
            'secret' => 123,
            'php_errors' => [
                'log' => true,
            ],
        ]);
        $containerBuilder->loadFromExtension('doctrine', [
            'dbal' => [
                'url' => '%env(resolve:DATABASE_URL)%',
            ],
        ]);
    }

    public function getProjectDir(): string
    {
        return $this->getRootDir();
    }

    public function getRootDir(): string
    {
        return $this->testRootDir;
    }

    /**
     * @return void
     */
    public function process(ContainerBuilder $container) {}
}
