<?php

declare (strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Error;

class NotImplementedException extends \InvalidArgumentException
{
    /**
     * Console exit status code when command fail with this exception.
     *
     * Number choice is arbitrary and in the 1-125 range.
     */
    const CONSOLE_EXIT_STATUS = 9;
}
