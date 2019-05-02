<?php

namespace Odb;

use Enum\RgtEnum;
use Yaf\Registry;

class Oredis
{
    /** @var \Redis[] */
    private static $instance = [];
    private static $current = 'default';

    /**
     * @param string $connect
     * @return \Redis
     * @throws \Exception
     */
    public static function getRedis(string $connect = 'default')
    {
        if (!isset(self::$instance[$connect])) {
            self::connect($connect);
        }
        return self::$instance[$connect];
    }

    public static function setConnect(string $connect)
    {
        self::$current = $connect;
    }


    public static function __callStatic($method, $parameters)
    {
        $redis = self::getRedis(self::$current);
        if (!$parameters) return false;
        return $redis->$method(...$parameters);
    }

    protected static function connect($connect)
    {
        $conf = Registry::get(RgtEnum::DB_CONF)['redis'];
        if (!isset($conf[$connect])) throw new \Exception($connect . '配置异常');
        $conf = $conf[$connect];
        $redis = new \Redis();
        $redis->connect($conf['host'], $conf['port'], $conf['time_out'], null, 100);
        if (!empty($conf['password'])) $redis->auth($conf['password']);
        $redis->select($conf['database']);
        self::$instance[$connect] = $redis;
    }

}