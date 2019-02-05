<?php

declare(strict_types=1);

namespace Lamoda\CleanerBundleTests;

use Lamoda\Cleaner\CleanerInterface;
use Lamoda\Cleaner\DB\Config\DBCleanerConfig;
use Lamoda\Cleaner\DB\DoctrineDBALCleaner;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class LamodaCleanerBundleTest extends KernelTestCase
{
    public function testBasicConfiguration(): void
    {
        $kernel = static::bootKernel();
        $container = $kernel->getContainer();

        $this->assertTrue(
            $container->has('test.lamoda_cleaner.db'),
            'Should have "lamoda_cleaner.db" cleaner collection service'
        );
        $dbCollection = $container->get('test.lamoda_cleaner.db');
        $this->assertInstanceOf(CleanerInterface::class, $dbCollection);

        $this->assertTrue(
            $container->has('test.lamoda_cleaner.db.dummy'),
            'Should have "lamoda_cleaner.db.dummy" cleaner service'
        );
        $dummyCleaner = $container->get('test.lamoda_cleaner.db.dummy');
        $this->assertInstanceOf(CleanerInterface::class, $dummyCleaner);

        $this->assertTrue(
            $container->has('test.lamoda_cleaner.db.dummy.config'),
            'Should have "lamoda_cleaner.db.dummy.config" cleaner configuration'
        );
        $dummyConfig = $container->get('test.lamoda_cleaner.db.dummy.config');
        $this->assertInstanceOf(DBCleanerConfig::class, $dummyConfig);

        $this->assertFalse($dummyConfig->isTransactional());

        $queries = $dummyConfig->getQueries();
        $this->assertCount(1, $queries);

        $queryConfig = reset($queries);
        $this->assertSame("SELECT 'dummy'", $queryConfig->getQuery());
        $this->assertSame([], $queryConfig->getParameters());
        $this->assertSame([], $queryConfig->getTypes());
    }

    public function testRegisterOnlyCustomCleaner(): void
    {
        $kernel = static::bootKernel([
            'environment' => 'custom_cleaner',
        ]);
        $container = $kernel->getContainer();

        $this->assertTrue(
            $container->has('test.lamoda_cleaner.db'),
            'Should have "lamoda_cleaner.db" cleaner collection service'
        );

        $this->assertTrue(
            $container->has('test.lamoda_cleaner.db.custom'),
            'Should have "lamoda_cleaner.db.custom" cleaner service'
        );
        $customCleaner = $container->get('test.lamoda_cleaner.db.custom');
        $this->assertInstanceOf(DoctrineDBALCleaner::class, $customCleaner);

        $application = new Application($kernel);
        $command = $application->find('cleaner:clear');
        $commandTester = new CommandTester($command);

        $logHandler = $container->get('test.monolog.handler.main');
        $logHandler->clear();

        $commandTester->execute([
            'command' => $command->getName(),
            'group' => 'db',
            'cleaner' => 'custom',
        ]);

        $this->assertEquals(0, $commandTester->getStatusCode());

        $this->assertTrue(
            $logHandler->hasRecordThatContains("SELECT 'custom'", Logger::DEBUG),
            'Should log query from custom cleaner'
        );
    }
}
