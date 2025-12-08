<?php

declare (strict_types=1);

namespace MakinaCorpus\DbToolsBundle;

use MakinaCorpus\DbToolsBundle\Bridge\Symfony\DbToolsBundle as TheRealBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @deprecated
 *   This class is deprecated and will be remove in next major.
 * @see \MakinaCorpus\DbToolsBundle\Bridge\Symfony\DbToolsBundle
 */
class DbToolsBundle extends TheRealBundle
{
    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        \trigger_deprecation(
            'makinacorpus/db-tools-bundle',
            '2.0.0',
            "Class %s is deprecated and will be removed in next major version, please use %s instead.",
            __CLASS__,
            TheRealBundle::class,
        );

        parent::build($container);
    }
}
