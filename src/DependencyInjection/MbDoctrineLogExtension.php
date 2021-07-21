<?php

declare(strict_types=1);

namespace Mb\DoctrineLogBundle\DependencyInjection;

use Doctrine\Common\EventSubscriber;
use Mb\DoctrineLogBundle\EventSubscriber\Logger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * Class MbDoctrineLogExtension
 * @package Mb\DoctrineLog\DependencyInjection
 */
class MbDoctrineLogExtension extends Extension
{
    /**
     * @inheritDoc
     */
    public function load(array $configs, ContainerBuilder $containerBuilder)
    {
        $loader = new XmlFileLoader($containerBuilder, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $configuration = $this->getConfiguration($configs, $containerBuilder);
        $config = $this->processConfiguration($configuration, $configs);

        $definition = $containerBuilder->getDefinition('mb_doctrine_log.event_subscriber.logger');
        $definition->setArgument(4, $config['ignore_properties']);
    }
}
