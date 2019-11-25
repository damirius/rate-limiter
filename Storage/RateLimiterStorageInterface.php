<?php

namespace Damirius\RateLimiter\Storage;

/**
 * Interface RateLimiterStorageInterface
 */
interface RateLimiterStorageInterface
{
    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key);

    /**
     * @param string   $key
     * @param string   $value
     * @param int|null $ttl
     * @return void
     */
    public function set(string $key, string $value, ?int $ttl);

    /**
     * @param string $key
     * @return void
     */
    public function delete(string $key);
}
