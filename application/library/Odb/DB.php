<?php

namespace Odb;


use Enum\RgtEnum;
use Yaf\Registry;

class DB
{
    /** @var \PDO[] */
    protected static $instances = [];
    protected static $conf = [];

    /**
     * @param string $connect
     * @return \PDO
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
     * @return SqlBuilder
     * @throws \Exception
     */
    public static function table(string $table, string $connect = 'default')
    {
        $db = self::getDB($connect);
        return (new SqlBuilder($db, self::$conf[$connect]['prefix']))->table($table);
    }

    protected static function connect(string $connect) : void
    {
        if (!self::$conf) {
            self::$conf = Registry::get(RgtEnum::DB_CONF)['db'];
        }
        if (!isset(self::$conf[$connect])) throw new \Exception($connect.'没有数据库连接配置');
        $conf = self::$conf[$connect];
        $dsn = $conf['driver'] . ":host={$conf['host']};port={$conf['port']};dbname={$conf['dbname']};charset={$conf['charset']}";
        $conf['params'][\PDO::ATTR_PERSISTENT] = $conf['pconnect'] ? true : false;
        $conf['params'][\PDO::ATTR_TIMEOUT] = $conf['time_out'] ? $conf['time_out'] : 3;
        $conf['params'][\PDO::ATTR_ERRMODE] = $conf['throw_exception'] ? \PDO::ERRMODE_EXCEPTION : \PDO::ERRMODE_SILENT;
        self::$instances[$connect] = new \PDO($dsn, $conf['username'], $conf['password'], $conf['params']);
    }

    private function __construct() {}
    private function __clone() {}
}