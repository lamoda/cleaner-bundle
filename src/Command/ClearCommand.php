<?php

declare(strict_types=1);

namespace Lamoda\CleanerBundle\Command;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCommand extends Command
{
    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();

        $this->container = $container;
    }

    protected function configure()
    {
        $this->setName('cleaner:clear')
            ->setDescription('Executes storage cleaners')
            ->addArgument('group', InputArgument::REQUIRED, 'Storage group')
            ->addArgument('cleaner', InputArgument::OPTIONAL, 'Cleaner name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $groupName = $input->getArgument('group');
        $cleanerName = $input->getArgument('cleaner');

        $serviceName = 'lamoda_cleaner.' . $groupName;
        if ($cleanerName != '') {
            $serviceName .= '.' . $cleanerName;
        }

        $cleaner = $this->container->get($serviceName);
        $cleaner->clear();

        return 0;
    }
}
