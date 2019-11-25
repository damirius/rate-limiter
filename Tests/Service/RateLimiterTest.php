<?php

namespace Damirius\RateLimiter\Tests\Service;

use PHPUnit\Framework\TestCase;
use Damirius\RateLimiter\Service\RateLimiter;
use Damirius\RateLimiter\Storage\RateLimiterStorageInterface;

/**
 * Class RateLimiterTest
 */
class RateLimiterTest extends TestCase
{

    /**
     * Asserts that checkAndReturn properly calculates remaining number of requests
     * @group time-sensitive
     */
    public function testCheckAndReturn()
    {
        $ip = "127.0.0.1";
        $period = 5;
        $limit = 2;
        $domain = 'default';

        $timeKey = $domain.'-'.$ip.'-time';
        $allowanceKey = $domain.'-'.$ip.'-allowance';
        $time = time();

        $storage = $this->createMock(RateLimiterStorageInterface::class);
        $storage->expects(self::any())->method('get')
            ->withConsecutive(
                //first call
                [$timeKey],
                //second call
                [$timeKey],
                [$timeKey],
                [$allowanceKey],
                //third call
                [$timeKey],
                [$timeKey],
                [$allowanceKey],
                //fourth call where we fake that we are calling in the future after reset period is done
                [$timeKey],
                [$timeKey],
                [$allowanceKey]
            )
            ->willReturnOnConsecutiveCalls(
                //first call
                null,
                //second call
                $time,
                $time,
                1.0,
                //third call
                $time,
                $time,
                0.0,
                //fourth call where we fake that we are calling in the future
                $time-$period,
                $time-$period,
                0.0
            );
        $rateLimit = new RateLimiter($limit, $period, $domain, $storage);

        for ($i = 0; $i <= $limit; $i++) {
            $this->assertEquals($limit - $i, $rateLimit->checkAndReturn($ip));
        }
        //fake some time
        $this->assertEquals($limit, $rateLimit->checkAndReturn($ip));
    }

    /**
     * Asserts that reset method calls storage delete with proper keys
     */
    public function testReset()
    {
        $storage = $this->createMock(RateLimiterStorageInterface::class);
        $ip = "127.0.0.1";
        $period = 5;
        $limit = 2;
        $domain = 'default';
        $timeKey = $domain.'-'.$ip.'-time';
        $allowanceKey = $domain.'-'.$ip.'-allowance';

        $storage->expects(self::exactly(2))->method('delete')->withConsecutive(
            [$timeKey],
            [$allowanceKey]
        );

        $rateLimit = new RateLimiter($limit, $period, $domain, $storage);
        $rateLimit->reset($ip);
    }
}
