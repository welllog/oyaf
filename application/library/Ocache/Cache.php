<?php

namespace Ocache;

use Psr\SimpleCache\CacheInterface;

class Cache
{
    /** @var CacheInterface */
    protected static $cacher;

    public static function init(CacheInterface $cacher)
    {
        if (null === self::$cacher) {
            self::$cacher = $cacher;
        }
        return self::$cacher;
    }

    public static function getCacher()
    {
        if (null === self::$cacher) {
            throw new \Exception('please init cache');
        }
        return self::$cacher;
    }

    public static function get($key, $default = null)
    {
        return self::getCacher()->get($key, $default);
    }

    public static function set($key, $value, $ttl = null)
    {
        return self::getCacher()->set($key, $value, $ttl);
    }

    public static function delete($key)
    {
        return self::getCacher()->delete($key);
    }

    public static function clear()
    {
        return self::getCacher()->clear();
    }

    public static function getMultiple($keys, $default = null)
    {
        return self::getCacher()->getMultiple($keys, $default);
    }

    public static function setMultiple($values, $ttl = null)
    {
        return self::getCacher()->setMultiple($values, $ttl);
    }

    public static function deleteMultiple($keys)
    {
        return self::getCacher()->deleteMultiple($keys);
    }

    public static function has($key)
    {
        return self::getCacher()->has($key);
    }

    public static function increment($key, $step = 1)
    {
        return self::getCacher()->increment($key, $step);
    }

    public static function decrement($key, $step = 1)
    {
        return self::getCacher()->decrement($key, $step);
    }
}