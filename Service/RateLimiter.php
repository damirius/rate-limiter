<?php

namespace Damirius\RateLimiter\Service;

use Damirius\RateLimiter\Storage\RateLimiterStorageInterface;

/**
 * Main RateLimiter service used for checking limits
 */
class RateLimiter
{
    private const TIME_KEY = 'time';
    private const ALLOWANCE_KEY = 'allowance';

    /**
     * @var int
     */
    private $limit;

    /**
     * @var int
     */
    private $period;

    /**
     * @var string
     */
    private $domain;

    /**
     * @var RateLimiterStorageInterface
     */
    private $rateLimiterStorage;

    /**
     * RateLimit constructor.
     * @param int                       $limit
     * @param int                       $period
     * @param string                    $domain
     * @param RateLimiterStorageInterface $rateLimiterStorage
     */
    public function __construct(
        int $limit,
        int $period,
        string $domain,
        RateLimiterStorageInterface $rateLimiterStorage
    ) {
        $this->limit = $limit;
        $this->period = $period;
        $this->domain = $domain;
        $this->rateLimiterStorage = $rateLimiterStorage;
    }

    /**
     * Rate Limiting
     *
     * @param string $identifier
     * @param float  $use
     * @return int
     */
    public function checkAndReturn(string $identifier, float $use = 1.0) : int
    {
        $rate = $this->limit / $this->period;
        $timeKey = $this->getTimeKey($identifier);
        $allowanceKey = $this->getAllowanceKey($identifier);
        if ($this->rateLimiterStorage->get($timeKey)) {
            $currentTime = time();
            $timeDifference = $currentTime - (int) $this->rateLimiterStorage->get($timeKey);
            $this->rateLimiterStorage->set($timeKey, $currentTime, $this->period);
            $allow = (float) $this->rateLimiterStorage->get($allowanceKey);
            $allow += $timeDifference * $rate;
            if ($allow > $this->limit) {
                $allow = $this->limit;
            }
            if ($allow < $use) {
                $this->rateLimiterStorage->set($allowanceKey, $allow, $this->period);

                return 0;
            } else {
                $this->rateLimiterStorage->set($allowanceKey, $allow - $use, $this->period);

                return (int) ceil($allow);
            }
        } else {
            $this->rateLimiterStorage->set($timeKey, time(), $this->period);
            $this->rateLimiterStorage->set($allowanceKey, $this->limit - $use, $this->period);

            return $this->limit;
        }
    }

    /**
     * @return int
     */
    public function getLimit() : int
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getPeriod() : int
    {
        return $this->period;
    }

    /**
     * @param string $identifier
     * @return int
     */
    public function getResetTime(string $identifier) : int
    {
        $timeKey = $this->getTimeKey($identifier);

        return $this->rateLimiterStorage->get($timeKey) ?
            $this->period - (time() - $this->rateLimiterStorage->get($timeKey)) : 0;
    }

    /**
     * @param string $identifier
     */
    public function reset(string $identifier)
    {
        $timeKey = $this->getTimeKey($identifier);
        $allowanceKey = $this->getAllowanceKey($identifier);

        $this->rateLimiterStorage->delete($timeKey);
        $this->rateLimiterStorage->delete($allowanceKey);
    }

    /**
     * @param string $identifier
     * @return string
     */
    private function getTimeKey(string $identifier) : string
    {
        return $this->getKeyName($identifier, $this::TIME_KEY);
    }

    /**
     * @param string $identifier
     * @return string
     */
    private function getAllowanceKey(string $identifier) : string
    {
        return $this->getKeyName($identifier, $this::ALLOWANCE_KEY);
    }

    /**
     * @param string $identifier
     * @param string $type
     * @return string
     */
    private function getKeyName(string $identifier, string $type) : string
    {
        $uniqueId = $this->domain.'-'.$identifier.'-'.$type;

        return $uniqueId;
    }
}
