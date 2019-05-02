<?php

namespace Odb;


use Enum\RgtEnum;
use Yaf\Registry;

class CDB
{
    protected static $instances = [];
    protected static $batch = [];
    protected static $consistency = [
        'one' => \Cassandra::CONSISTENCY_LOCAL_ONE,
        'quorum' => \Cassandra::CONSISTENCY_LOCAL_QUORUM,
        'any' => \Cassandra::CONSISTENCY_ANY,
        'all' => \Cassandra::CONSISTENCY_ALL,
    ];

    /**
     * @param string $connect
     * @return mixed
     * @throws \Exception
     */
    public static function getDB(string $connect = 'default')
    {
        if (!isset(self::$instances[$connect])) {
            self::connect($connect);
        }
        return self::$instances[$connect];
    }

    /**
     * @param string $table
     * @param string $connect
     * @return CqlBuilder
     * @throws \Exception
     */
    public static function table(string $table, string $connect = 'default')
    {
        $db = self::getDB($connect);
        $handler = (new CqlBuilder($db))->table($table);
        if (isset(self::$batch[$connect])) $handler->setBatch(self::$batch[$connect]);
        return $handler;
    }

    /**
     * Cassandra::BATCH_LOGGED 原子操作   Cassandra::BATCH_UNLOGGED某些语句可能会失败  Cassandra::BATCH_COUNTER计数器更新
     * @param \Cassandra::BATCH_LOGGED|\Cassandra::BATCH_UNLOGGED|\Cassandra::BATCH_COUNTER $batch
     * @param string $connect
     */
    public static function batch($batch = \Cassandra::BATCH_LOGGED, string $connect = 'default')
    {
        self::$batch[$connect] = new \Cassandra\BatchStatement($batch);
    }

    public static function batchExec($async = false, string $connect = 'default')
    {
        $res = null;
        try {
            if ($async) {
                $res = self::getDB($connect)->executeAsync(self::$batch[$connect]);
            } else {
                self::getDB($connect)->execute(self::$batch[$connect]);
            }
        } catch (\Exception $e) {
            throw new \Exception('batch exec err; ' . $e->getMessage() . '; cql: ' . json_encode(self::$batch[$connect]->cql));
        }
        self::$batch[$connect] = null;
        if ($async) {
            return (new AsyncCqlResponse($res));
        }
        return $res;
    }

    protected static function connect(string $connect) : void
    {
        $conf = Registry::get(RgtEnum::DB_CONF)['cdb'];
        if (!isset($conf[$connect])) throw new \Exception($connect.'没有数据库连接配置');
        $conf = $conf[$connect];
        try {
            $consis = self::$consistency[$conf['consistency']] ?? \Cassandra::CONSISTENCY_LOCAL_ONE;
            $cluster = \Cassandra::cluster()->withContactPoints($conf['host'])->withPort($conf['port'])
                ->withDefaultConsistency($consis) // 一致性
                ->withConnectionsPerHost($conf['min_links'], $conf['max_links'])   // 连接池大小
                ->withConnectionHeartbeatInterval($conf['heart_beat_intval']) // 心跳间隔
                ->withConnectTimeout($conf['connect_timeout']) // 连接超时
                ->withRequestTimeout($conf['request_timeout']); // 请求超时时间

            if ($conf['username'] && $conf['password']) {
                $cluster->withCredentials($conf['username'], $conf['password']);
            }
            $session = $cluster->build()->connect($conf['keyspace']);

        } catch (\Exception $e) {
            throw new \Exception('connect cassandra err: ' . $e->getMessage());
        }
        self::$instances[$connect] = $session;
    }

    private function __construct() {}
    private function __clone() {}
}