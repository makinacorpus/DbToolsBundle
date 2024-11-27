<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Datasource;

use MakinaCorpus\DbToolsBundle\Anonymization\Datasource\Expression\Parser;
use MakinaCorpus\DbToolsBundle\Anonymization\Datasource\Expression\Token;

class Expression
{
    /** @var Token[] */
    private array $tokens = [];

    /**
     * All other data than the raw text is here only for error handling and
     * building helping error messages for end-users.
     *
     * @param string $raw
     *   User text.
     * @param string $datasource
     *   Datasource in which this expression is found.
     * @param int $number
     *   Expression number in datasource.
     */
    public function __construct(string $raw, string $datasource, int $number)
    {
        $this->tokens = (new Parser($raw, $datasource, $number))->parse();
    }

    /**
     * Execute given expression over the given context.
     */
    public function execute(Context $context): ?string
    {
        $ret = '';
        foreach ($this->tokens as $token) {
            \assert($token instanceof Token);
            $ret .= $token->execute($context);
        }
        return $ret;
    }

    /**
     * @internal
     *   For unit tests.
     */
    public function toArray(): array
    {
        return $this->tokens;
    }
}
