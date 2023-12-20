<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\Command\Anonymization;

use MakinaCorpus\DbToolsBundle\Test\FunctionalKernelTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class AnonymizeCommandTest extends FunctionalKernelTestCase
{
    public function testExecute(): void
    {
        self::skipIfNoDatabase();

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('db-tools:anonymization:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                '--local-database' => true
            ],
            [
                'interactive' => false,
                'capture_stderr_separately' => true
            ]
        );

        self::assertCommandIsSuccessful($commandTester);
    }

    public function testExecuteOnLocalDatabaseInProdIsForbidden(): void
    {
        self::skipIfNoDatabase();

        $kernel = self::bootKernel(['environment' => 'prod']);
        $application = new Application($kernel);

        $command = $application->find('db-tools:anonymization:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                '--local-database' => true,
                '--no-restore' => true,
            ],
            [
                'interactive' => false,
                'capture_stderr_separately' => true
            ]
        );

        self::assertCommandIsSuccessful($commandTester);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('CAUTION', $output);
        $this->assertStringContainsString('local database in production', $output);
    }
}
