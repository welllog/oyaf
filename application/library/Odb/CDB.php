<?php

namespace Odb;

class CDB
{
    const DEFAULT_CONN = 'default';

    /**
     * @param string $connect
     * @return CqlOperator
     */
    public static function connect(string $connect = self::DEFAULT_CONN)
    {
        return new CqlOperator($connect);
    }

    /**
     * @param string $table
     * @param string $connect
     * @return CqlBuilder
     * @throws \Exception
     */
    public static function table(string $table, string $connect = self::DEFAULT_CONN)
    {
        return self::connect($connect)->table($table);
    }

    /**
     * @param string $cql
     * @param string $connect
     * @return CqlOperator
     * @throws \Exception
     */
    public static function prepare(string $cql, string $connect = self::DEFAULT_CONN)
    {
        return self::connect($connect)->prepare($cql);
    }

    private function __construct() {}
    private function __clone() {}
}