<?php

declare (strict_types=1);

namespace MakinaCorpus\DbToolsBundle;

use MakinaCorpus\DbToolsBundle\DependencyInjection\Compiler\DbToolsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DbToolsBundle extends Bundle
{
    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new DbToolsPass());
    }
}
