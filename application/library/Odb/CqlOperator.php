<?php
/**
 * Created by PhpStorm.
 * User: chentairen
 * Date: 2019/5/21
 * Time: 下午11:09
 */

namespace Odb;


use Enum\RgtEnum;
use Yaf\Registry;

class CqlOperator
{
    protected static $instances = [];
    protected static $consistency = [
        'one' => \Cassandra::CONSISTENCY_LOCAL_ONE,
        'quorum' => \Cassandra::CONSISTENCY_LOCAL_QUORUM,
        'any' => \Cassandra::CONSISTENCY_ANY,
        'all' => \Cassandra::CONSISTENCY_ALL,
    ];

    public static function getConn(string $connect)
    {
        if (!isset(self::$instances[$connect])) {
            $conf = Registry::get(RgtEnum::DB_CONF)['cdb'];
            if (!isset($conf[$connect])) throw new \Exception($connect . '没有数据库连接配置');
            $conf = $conf[$connect];
            try {
                $consis = self::$consistency[$conf['consistency']] ?? \Cassandra::CONSISTENCY_LOCAL_ONE;
                $cluster = \Cassandra::cluster()->withContactPoints($conf['host'])->withPort($conf['port'])
                    ->withDefaultConsistency($consis)// 一致性
                    ->withConnectionsPerHost($conf['min_links'], $conf['max_links'])// 连接池大小
                    ->withConnectionHeartbeatInterval($conf['heart_beat_intval'])// 心跳间隔
                    ->withConnectTimeout($conf['connect_timeout'])// 连接超时
                    ->withRequestTimeout($conf['request_timeout']); // 请求超时时间

                if ($conf['username'] && $conf['password']) {
                    $cluster->withCredentials($conf['username'], $conf['password']);
                }
                self::$instances[$connect] = $cluster->build()->connect($conf['keyspace']);

            } catch (\Exception $e) {
                throw new \Exception('connect cassandra err: ' . $e->getMessage());
            }
        }
        return self::$instances[$connect];
    }

    protected $db;
    protected $batch;
    protected $async = false;
    protected $statement;
    protected $cql;
    protected $result;

    public function __construct($connect)
    {
        $this->db = self::getConn($connect);
    }

    /**
     * @param string $table
     * @return CqlBuilder
     */
    public function table(string $table)
    {
        return (new CqlBuilder())->setOperator($this)->table($table);
    }

    /**
     * Cassandra::BATCH_LOGGED 原子操作   Cassandra::BATCH_UNLOGGED某些语句可能会失败  Cassandra::BATCH_COUNTER计数器更新
     * @param \Cassandra::BATCH_LOGGED|\Cassandra::BATCH_UNLOGGED|\Cassandra::BATCH_COUNTER $batch
     */
    public function batch($batch = \Cassandra::BATCH_LOGGED)
    {
        $this->batch = new \Cassandra\BatchStatement($batch);
    }

    /**
     * @return null|AsyncCqlResponse
     * @throws \Exception
     */
    public function batchExec()
    {
        $res = null;
        try {
            if ($this->async) {
                $res = $this->db->executeAsync($this->batch);
            } else {
                $this->db->execute($this->batch);
            }
        } catch (\Exception $e) {
            throw new \Exception('batch exec err; ' . $e->getMessage() . '; cql: ' . json_encode($this->batch->cql));
        }
        $this->batch = null;
        if ($this->async) {
            return new AsyncCqlResponse($res);
        }
        return $res;
    }

    /**
     * @param $cql
     * @return CqlOperator
     * @throws \Exception
     */
    public function prepare($cql)
    {
        try {
            $this->cql = $cql;
            $this->statement = $this->db->prepare($cql);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage().' ; cql: ' . $cql);
        }
        return $this;
    }

    /**
     * @return CqlOperator
     */
    public function async()
    {
        $this->async = true;
        return $this;
    }

    /**
     * @param $params
     * @param string $cql
     * @return CqlOperator
     * @throws \Exception
     */
    public function execute($params, $cql = '')
    {
        if ($this->statement) {
            $execCql = $this->statement;
            $cql = $this->cql;
        } else {
            $execCql = $cql;
        }
        try {
            if ($this->batch) {
                // 批处理直接填充参数
                $this->batch->add($execCql, $params);
                $this->batch->cql[] = $cql;
            } else {
                if ($this->async) {
                    $this->result = $this->db->executeAsync($execCql, ['arguments' => $params]);
                } else {
                    $this->result = $this->db->execute($execCql, ['arguments' => $params]);
                }
            }
        } catch (\Exception $e) {
            $cql = $this->getRcql($cql, $params);
            throw new \Exception($e->getMessage().' ; cql: ' . $cql);
        }
        return $this;
    }

    /**
     * @param $params
     * @param string $cql
     * @return CqlOperator
     * @throws \Exception
     */
    public function origExecute($params, $cql = '')
    {
        if ($this->statement) {
            $execCql = $this->statement;
            $cql = $this->cql;
        } else {
            $execCql = $cql;
        }
        try {
            $this->result = $this->db->execute($execCql, $params);
        } catch (\Exception $e) {
            $cql = $this->getRcql($cql, $params['arguments']);
            throw new \Exception($e->getMessage().' ; cql: ' . $cql);
        }
        return $this;
    }

    /**
     * @param \Closure|null $handler
     * @return array|AsyncCqlResponse
     */
    public function get(\Closure $handler = null)
    {
        if ($this->async) {
            return new AsyncCqlResponse($this->result, $handler);
        }
        $result = [];
        foreach ($this->result as $row) {
            $result[] = $handler ? $handler($row) : $row;
        }
        return $result;
    }

    /**
     * @param \Closure|null $handler
     * @return mixed|null|AsyncCqlResponse
     */
    public function first(\Closure $handler = null)
    {
        if ($this->async) {
            return new AsyncCqlResponse($this->result, $handler);
        }
        if ($this->result[0] === null) return null;
        return $handler ? $handler($this->result[0]) : $this->result[0];
    }

    /**
     * @param $column
     * @param \Closure|null $handler
     * @return mixed|null|AsyncCqlResponse
     */
    public function value($column, \Closure $handler = null)
    {
        if ($this->async) {
            return (new AsyncCqlResponse($this->result, $handler))->setColumn($column);
        }
        if ($this->result[0] === null) return null;
        if ($this->result[0][$column] === null) return null;
        return $handler ? $handler($this->result[0][$column]) : $this->result[0][$column];
    }

    /**
     * @param $column
     * @param string $key
     * @param \Closure|null $handler
     * @return array
     */
    public function pluck($column, $key = '', \Closure $handler = null)
    {
        $result = [];
        if ($key) {
            foreach ($this->result as $row) {
                $row = $handler ? $handler($row) : $row;
                $result[$row[$key]] = $row[$column];
            }
        } else {
            foreach ($this->result as $row) {
                $row = $handler ? $handler($row) : $row;
                $result[] = $row[$column];
            }
        }
        return $result;
    }

    /**
     * @return AsyncCqlResponse
     */
    public function getResult()
    {
        if ($this->async) {
            return new AsyncCqlResponse($this->result);
        }
        return $this->result;
    }

    /**
     * @param $cql
     * @param $params
     * @return string
     */
    protected function getRcql($cql, $params)
    {
        $arr = explode('?', $cql);
        $cql = '';
        foreach ($arr as $k => $v) {
            $cql .= $v . ($params[$k] ?? '');
        }
        if (!$cql) $cql = $arr[0];
        return $cql;
    }
}