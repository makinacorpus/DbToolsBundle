<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Test;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use MakinaCorpus\DbToolsBundle\Error\NotImplementedException;

abstract class FunctionalKernelTestCase extends KernelTestCase
{
    protected static function skipIfNoDatabase(): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped("'DATABASE_URL' environment variable is missing.");
        }
    }

    protected static function assertCommandIsSuccessful(CommandTester|int $actual, string $message = ''): void
    {
        if ($actual instanceof CommandTester) {
            $status = $actual->getStatusCode();
        } else {
            $status = $actual;
        }

        if (NotImplementedException::CONSOLE_EXIT_STATUS === $status) {
            self::markTestIncomplete("Not implemented feature.");
        }

        self::assertSame(0, $status);
    }
}
