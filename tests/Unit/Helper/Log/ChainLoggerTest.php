<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Helper\Log;

use MakinaCorpus\DbToolsBundle\Helper\Log\ChainLogger;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class ChainLoggerTest extends TestCase
{
    private ?\ReflectionClass $rc = null;

    protected function setUp(): void
    {
        if (null === $this->rc) {
            $this->rc = new \ReflectionClass(ChainLogger::class);
        }
    }

    private function extractLoggers(ChainLogger $obj): array
    {
        return $this->rc->getProperty('loggers')->getValue($obj);
    }

    public function testAddLogger(): void
    {
        $chain = new ChainLogger();
        $logger1 = $this->createMock(LoggerInterface::class);
        $logger2 = $this->createMock(LoggerInterface::class);

        $chain->addLogger($logger1);
        $loggers = $this->extractLoggers($chain);
        self::assertCount(1, $loggers);
        self::assertContains($logger1, $loggers);


        $chain->addLogger($logger2);
        $loggers = $this->extractLoggers($chain);
        self::assertCount(2, $loggers);
        self::assertContains($logger2, $loggers);
    }

    #[Depends('testAddLogger')]
    public function testHasLogger(): void
    {
        $chain = new ChainLogger();
        $logger1 = $this->createMock(LoggerInterface::class);
        $logger2 = $this->createMock(LoggerInterface::class);

        $chain->addLogger($logger1);
        self::assertTrue($chain->hasLogger($logger1));
        self::assertFalse($chain->hasLogger($logger2));

        $chain->addLogger($logger2);
        self::assertTrue($chain->hasLogger($logger1));
        self::assertTrue($chain->hasLogger($logger2));
    }

    #[Depends('testAddLogger')]
    public function testLog(): void
    {
        $chain = new ChainLogger();
        $logger1 = $this->createMock(AbstractLogger::class);
        $logger2 = $this->createMock(AbstractLogger::class);

        $logger1->expects($this->exactly(2))->method('log');
        $logger2->expects($this->once())->method('log')->with(LogLevel::ERROR, "Error", []);

        $chain->addLogger($logger1);
        $chain->log(LogLevel::INFO, "Info");
        $chain->addLogger($logger2);
        $chain->log(LogLevel::ERROR, "Error");
    }
}
