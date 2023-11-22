<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class GdprifyCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        $this->markTestSkipped("Hard to test for know.");

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('db-tools:gdprify');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
    }
}
