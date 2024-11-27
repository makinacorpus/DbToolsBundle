<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Datasource;

class MultipleColumnFixedDatasource extends MultipleColumnDatasource
{
    #[\Override]
    public function random(Context $context): string|array
    {
        
    }
}
