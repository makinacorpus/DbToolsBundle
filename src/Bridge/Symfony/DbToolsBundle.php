<?php

declare (strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Bridge\Symfony;

use MakinaCorpus\DbToolsBundle\Bridge\Symfony\DependencyInjection\Compiler\DbToolsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use MakinaCorpus\DbToolsBundle\Bridge\Symfony\DependencyInjection\DbToolsExtension;

class DbToolsBundle extends Bundle
{
    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new DbToolsPass());
    }

    /**
     * Override is required for backward compatibility.
     *
     * Remove this method when MakinaCorpus\DbToolsBundle\DbToolsBundle legacy
     * class will be removed.
     */
    #[\Override]
    protected function getContainerExtensionClass(): string
    {
        return DbToolsExtension::class;
    }
}
