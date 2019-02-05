<?php

declare(strict_types=1);

namespace Lamoda\CleanerBundleTests\App;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Lamoda\CleanerBundle\LamodaCleanerBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
            new DoctrineBundle(),
            new MonologBundle(),
            new LamodaCleanerBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/config/config.yaml');
        $loader->load(__DIR__ . '/config/config_' . $this->getEnvironment() . '.yaml');
    }

    public function getCacheDir()
    {
        return $this->getProjectDir() . '/var/cache/' . $this->getEnvironment();
    }

    public function getLogDir()
    {
        return $this->getProjectDir() . '/var/log';
    }
}
