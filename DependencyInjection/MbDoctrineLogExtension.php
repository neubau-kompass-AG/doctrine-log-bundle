<?php

declare(strict_types=1);

namespace Mb\DoctrineLogBundle\DependencyInjection;

use Mb\DoctrineLogBundle\EventSubscriber\Logger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class MbDoctrineLogExtension extends Extension
{
    /** @inheritDoc */
    public function load(array $configs, ContainerBuilder $containerBuilder): void
    {
        $loader = new YamlFileLoader($containerBuilder, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $configuration = $this->getConfiguration($configs, $containerBuilder);
        $config = $this->processConfiguration($configuration, $configs);

        $definition = $containerBuilder->getDefinition(Logger::class);
        $definition->setArgument('$ignoreProperties', $config['ignore_properties']);
    }
}
