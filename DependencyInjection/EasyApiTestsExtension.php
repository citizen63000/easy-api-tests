<?php

namespace EasyApiTests\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class EasyApiTestsExtension extends Extension
{
    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Load configuration
        $config = (new Processor())->processConfiguration(new Configuration(), $configs);

        // Convert config as parameters
        $this->loadParametersFromConfiguration($config, $container);

        // Load services
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../config'));
        $loader->load('services.yml');
    }

    /**
     * @param array $loadedConfig
     * @param ContainerBuilder $container
     * @param string $parentKey
     */
    protected function loadParametersFromConfiguration(array $loadedConfig, ContainerBuilder $container, string $parentKey = 'easy_api_tests'): void
    {
        foreach ($loadedConfig as $parameter => $value) {
            if (is_array($value)) {
                $this->loadParametersFromConfiguration($value, $container, "{$parentKey}.{$parameter}");
            } else {
                $container->setParameter("{$parentKey}.{$parameter}", $value);
            }
        }
    }
}