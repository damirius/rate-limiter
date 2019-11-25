<?php

namespace Damirius\RateLimiter\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Damirius\RateLimiter\DependencyInjection\DamiriusRateLimiterExtension;
use Damirius\RateLimiter\Service\RateLimiter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class DamiriusRateLimiterExtensionTest
 */
class DamiriusRateLimiterExtensionTest extends TestCase
{
    /**
     * Assert that extension creates proper services
     */
    public function testLoad()
    {
        $extension = new DamiriusRateLimiterExtension();
        $config = [
            [
                'domains' => [
                    'testdomain' => [
                        'limit'  => 10,
                        'period' => 10,
                        'storage_service' => 'test.service1',
                    ],
                    'testdomain2' => [
                        'limit'           => 5,
                        'period'          => 4,
                        'storage_service' => 'test.service2',
                    ],
                ],
            ],
        ];
        $containerBuilderMock = $this->createMock(ContainerBuilder::class);
        $definition1 = new Definition(RateLimiter::class, [10, 10, 'testdomain', new Reference('test.service1')]);
        $name1 = 'damirius_rate_limiter.limiter.testdomain';
        $definition2 = new Definition(RateLimiter::class, [5, 4, 'testdomain2', new Reference('test.service2')]);
        $name2 = 'damirius_rate_limiter.limiter.testdomain2';
        $containerBuilderMock->expects(self::exactly(2))->method('setDefinition')
            ->withConsecutive(
                [$name1, $definition1],
                [$name2, $definition2]
            );
        $extension->load($config, $containerBuilderMock);
    }
}
