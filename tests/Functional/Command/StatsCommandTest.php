<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\Command;

use MakinaCorpus\DbToolsBundle\Test\FunctionalKernelTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class StatsCommandTest extends FunctionalKernelTestCase
{
    public function testExecute(): void
    {
        self::skipIfNoDatabase();

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('db-tools:stats');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertCommandIsSuccessful($commandTester);
    }
}
