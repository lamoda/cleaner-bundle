<?php

declare(strict_types=1);

namespace Lamoda\CleanerBundleTests\Command;

use Lamoda\CleanerBundle\Command\ClearCommand;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class ClearCommandTest extends KernelTestCase
{
    /** @var ClearCommand */
    private $command;

    /** @var CommandTester */
    private $commandTester;

    /** @var TestHandler */
    private $logHandler;

    protected function setUp()
    {
        parent::setUp();

        $kernel = static::bootKernel();
        $application = new Application($kernel);

        $this->command = $application->find('cleaner:clear');
        $this->commandTester = new CommandTester($this->command);

        $this->logHandler = $kernel->getContainer()->get('test.monolog.handler.main');
        $this->logHandler->clear();
    }

    public function testWhenWithoutParametersShouldReturnError(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "group").');

        $this->commandTester->execute([
            'command' => $this->command->getName(),
        ]);
    }

    public function testWhenCallWholeGroupShouldReturnSuccess(): void
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'group' => 'db',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $this->assertTrue(
            $this->logHandler->hasRecordThatContains("SELECT 'dummy'", Logger::DEBUG),
            'Should log query from first cleaner in group'
        );
        $this->assertTrue(
            $this->logHandler->hasRecordThatContains("SELECT 'dummy2'", Logger::DEBUG),
            'Should log query from second cleaner in group'
        );
    }

    public function testWhenCallSingleCleanerShouldReturnSuccess(): void
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'group' => 'db',
            'cleaner' => 'dummy',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());

        $this->assertTrue(
            $this->logHandler->hasRecordThatContains("SELECT 'dummy'", Logger::DEBUG),
            'Should log query from required cleaner'
        );
        $this->assertFalse(
            $this->logHandler->hasRecordThatContains("SELECT 'dummy2'", Logger::DEBUG),
            'Should not execute other cleaner in group'
        );
    }
}
