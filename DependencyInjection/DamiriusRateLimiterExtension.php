<?php

namespace Damirius\RateLimiter\DependencyInjection;

use Damirius\RateLimiter\Service\RateLimiter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class DamiriusRateLimiterExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        foreach ($config['domains'] as $name => $domain) {
            if (is_array($domain) && $name) {
                $serviceDefinition = new Definition(RateLimiter::class, [$domain['limit'], $domain['period'], $name, new Reference($domain['storage_service'])]);
                $serviceId = 'damirius_rate_limiter.limiter.'.$name;
                $container->setDefinition($serviceId, $serviceDefinition);
            }
        }
    }
}
