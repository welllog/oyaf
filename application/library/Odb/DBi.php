<?php

namespace Odb;


use Enum\RgtEnum;
use Yaf\Registry;

class DBi
{
    protected static $instance;
    /** @var \mysqli[] */
    protected $links = [];
    protected $conf;

    /**
     * @param string $connect
     * @return \mysqli
     * @throws \Exception
     */
    public static function getDB(string $connect = 'default')
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        if (!isset(self::$instance->links[$connect])) {
            self::$instance->connect($connect);
        }
        return self::$instance->links[$connect];
    }

    /**
     * @param string $table
     * @param string $connect
     * @return MysqliSqlBuilder
     * @throws \Exception
     */
    public static function table(string $table, string $connect = 'default')
    {
        $db = self::getDB($connect);
        return (new MysqliSqlBuilder($db, self::$instance->conf[$connect]['prefix']))->table($table);
    }

    protected function connect(string $connect) : void
    {
        if (!$this->conf) {
            $this->conf = Registry::get(RgtEnum::DB_CONF)['db'];
        }
        if (empty($this->conf[$connect]) || $this->conf[$connect]['driver'] != 'mysql') throw new \Exception($connect.'数据库连接配置非法');
        $conf = $this->conf[$connect];
        $mysqli = new \mysqli($conf['host'], $conf['username'], $conf['password'], $conf['dbname'], $conf['port']);
        if ($mysqli->connect_errno) {
            throw new \Exception('Failed to connect to MySQL: (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
        }
        $mysqli->set_charset($conf['charset']);
        $this->links[$connect] = $mysqli;
    }

    private function __construct() {}
    private function __clone() {}

    public  function __wakeup() {
        self::$instance = $this;
    }

    public function __destruct()
    {
        foreach ($this->links as $cli) {
            $cli->close();
        }
    }
}