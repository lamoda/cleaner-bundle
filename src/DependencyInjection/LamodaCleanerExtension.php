<?php

declare(strict_types=1);

namespace Lamoda\CleanerBundle\DependencyInjection;

use Lamoda\Cleaner\CleanerCollection;
use Lamoda\Cleaner\DB\Config\DBCleanerConfigFactory;
use Lamoda\Cleaner\DB\DoctrineDBALCleaner;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class LamodaCleanerExtension extends ConfigurableExtension implements CompilerPassInterface
{
    private $supportedStorages = [
        'db' => [
            'configFactory' => DBCleanerConfigFactory::class,
        ],
    ];

    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $locator = new FileLocator(__DIR__ . '/../Resources/config');
        $loader = new YamlFileLoader($container, $locator);
        $loader->load('services.yml');

        $this->registerCleaners($mergedConfig, $container);
    }

    private function registerCleaners(array $config, ContainerBuilder $container): void
    {
        foreach ($config as $storageType => $cleanerList) {
            $storageCollectionId = $this->getStorageCleanerServiceId($storageType);
            $collectionDefinition = (new Definition(CleanerCollection::class))
                ->setPublic(false);
            $container->setDefinition($storageCollectionId, $collectionDefinition);

            foreach ($cleanerList as $name => $cleanerConfig) {
                $cleanerServiceId = $this->getCleanerServiceId($storageType, $name);
                $configName = $cleanerServiceId . '.config';

                $configFactoryClass = $this->supportedStorages[$storageType]['configFactory'];
                $configDefinition = (new Definition($configFactoryClass, [$cleanerConfig]))
                    ->setFactory([$configFactoryClass, 'create'])
                    ->setPublic(false);
                $container->setDefinition($configName, $configDefinition);

                $class = $cleanerConfig['class'];
                $definition = (new Definition($class))
                    ->setPublic(false)
                    ->setAutowired(true)
                    ->setArgument('$config', new Reference($configName))
                    ->addTag($storageCollectionId, ['alias' => $name]);

                if (is_a($class, DoctrineDBALCleaner::class, true)) {
                    $definition->setArgument('$connection', new Reference($cleanerConfig['dbal_connection']));
                }

                $container->setDefinition($cleanerServiceId, $definition);
            }
        }
    }

    public function process(ContainerBuilder $container)
    {
        $locateableServices = [];

        foreach ($this->supportedStorages as $storageType => $storageOptions) {
            $storageCollectionId = $this->getStorageCleanerServiceId($storageType);
            if (!$container->has($storageCollectionId)) {
                continue;
            }

            $locateableServices[$storageCollectionId] = new Reference($storageCollectionId);
            $collectionDefinition = $container->getDefinition($storageCollectionId);

            foreach ($container->findTaggedServiceIds($storageCollectionId) as $id => $tags) {
                $collectionDefinition->addMethodCall('addCleaner', [new Reference($id)]);
                foreach ($tags as $tag) {
                    $cleanerServiceId = $this->getCleanerServiceId($storageType, $tag['alias'] ?? '');
                    if (!$container->has($cleanerServiceId)) {
                        $container->setAlias($cleanerServiceId, $id);
                    }
                    $locateableServices[$cleanerServiceId] = new Reference($cleanerServiceId);
                }
            }
        }

        $locator = ServiceLocatorTagPass::register($container, $locateableServices);
        $container->setAlias('lamoda_cleaner.cleaner_locator', (string) $locator);
    }

    private function getStorageCleanerServiceId(string $storageType): string
    {
        return sprintf('lamoda_cleaner.%s', $storageType);
    }

    private function getCleanerServiceId(string $storageType, string $name): string
    {
        return sprintf('lamoda_cleaner.%s.%s', $storageType, $name);
    }
}
