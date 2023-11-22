<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class RestoreCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        $this->markTestSkipped("Hard to test for know.");

        // @phpstan-ignore-next-line
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('db-tools:restore');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
    }
}
