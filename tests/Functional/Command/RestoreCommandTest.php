<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\Command;

use MakinaCorpus\DbToolsBundle\Tests\FunctionalKernelTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class RestoreCommandTest extends FunctionalKernelTestCase
{
    public function testExecute(): void
    {
        self::skipIfNoDatabase();

        $this->markTestSkipped("Hard to test for know.");

        // @phpstan-ignore-next-line
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('db-tools:restore');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertCommandIsSuccessful($commandTester);
    }
}
