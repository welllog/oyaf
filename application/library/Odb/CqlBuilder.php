<?php
/**
 * Created by PhpStorm.
 * User: chentairen
 * Date: 2019/4/26
 * Time: 下午6:13
 */

namespace Odb;


class CqlBuilder
{
    protected $db;

    protected $statement;
    protected $cql;
    protected $params = [];
    protected $batch;
    protected $async = false;

    protected $cqlSlice = [
        'columns' => '', 'table' => '', 'ttl' => '', 'where' => '', 'group_by' => '', 'order_by' => '',
        'limit' => '', 'update' => '', 'insert' => '', 'delete' => ''
    ];

    protected $paramSlice = [
        'allow' => [], 'where' => [], 'insert' => [], 'update' => []
    ];

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function table($table)
    {
        $this->cqlSlice['table'] = $table;
        return $this;
    }

    public function select($columns)
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        if ($columns === []) {
            $this->cqlSlice['columns'] = ' * ';
            return $this;
        }
        $this->cqlSlice['columns'] = implode(',', $columns);
        return $this;
    }

    public function where(...$where)
    {
        if (empty($where[0])) return $this;
        $whereSql = '';
        $whereParams = [];
        if (is_array($where[0])) { // Two-dimensional array
            $whereSql .= '';
            foreach ($where[0] as $val) {
                if (isset($val[2])) {
                    $whereSql .= $val[0].' '.$val[1].' ? AND ';
                    $whereParams[] = $val[2];
                } else {
                    $whereSql .= $val[0].' = ? AND ';
                    $whereParams[] = $val[1];
                }
            }
            $whereSql = substr($whereSql, 0, -5);
        } else {
            if (isset($where[2])) {
                $whereSql .= $where[0].' '.$where[1].' ?';
                $whereParams[] = $where[2];
            } else {
                $whereSql .= $where[0].' = ?';
                $whereParams[] = $where[1];
            }
        }
        if ($this->cqlSlice['where'] === '') {
            $this->cqlSlice['where'] = ' WHERE ' . $whereSql;
        } else {
            $this->cqlSlice['where'] .= ' AND ' . $whereSql . ' ';
        }
        $this->paramSlice['where'] = array_merge($this->paramSlice['where'], $whereParams);
        return $this;
    }

    public function whereRaw(string $whereSql, array $whereParams)
    {
        if ($this->cqlSlice['where'] === '') {
            $this->cqlSlice['where'] = ' WHERE ' . $whereSql;
        } else {
            $this->cqlSlice['where'] .= ' AND ' . $whereSql;
        }
        $this->paramSlice['where'] = array_merge($this->paramSlice['where'], $whereParams);
        return $this;
    }

    public function whereIn(string $column, array $in)
    {
        $place_holders = implode(',', array_fill(0, count($in), '?'));
        $whereSql = $column . " in ({$place_holders})";
        if ($this->cqlSlice['where'] === '') {
            $this->cqlSlice['where'] = 'WHERE ' . $whereSql;
        } else {
            $this->cqlSlice['where'] .= ' AND ' . $whereSql;
        }
        $this->paramSlice['where'] = array_merge($this->paramSlice['where'], $in);
        return $this;
    }

    public function orderBy(string $column, string $order)
    {
        if ($this->cqlSlice['order_by'] === '') {
            $this->cqlSlice['order_by'] = ' ORDER BY ' . $column . ' ' . $order;
        } else {
            $this->cqlSlice['order_by'] .= ',' .  $column . ' ' . $order;
        }
        return $this;
    }

    public function groupBy($column)
    {
        $columns = is_array($column) ? $column : func_get_args();
        $columnStr = implode(',', $columns);
        $this->cqlSlice['group_by'] = ' GROUP BY ' . $columnStr . ' ';
        return $this;
    }

    public function limit(int $limit)
    {
        $this->cqlSlice['limit'] = ' LIMIT ' . intval($limit);
        return $this;
    }

    public function allow($columns)
    {
        $allows = is_array($columns) ? $columns : func_get_args();
        foreach ($allows as $a) {
            $this->paramSlice['allow'][$a] = '';
        }
        return $this;
    }

    public function ttl($timeout)
    {
        $this->cqlSlice['ttl'] = ' USING TTL ' . (int)$timeout;
        return $this;
    }

    /**
     * @param $insert
     * @return bool
     * @throws \Exception
     */
    public function insert($insert)
    {
        if (!$insert) return false;
        $insertStr = '(';
        $rep = '(';
        $allow = $this->paramSlice['allow'];
        $filter = ($allow !== []) ? true : false;
        $insertVal = [];
        foreach ($insert as $k => $v) {
            if (!$filter || isset($allow[$k])) {
                $insertStr .= $k . ',';
                $rep .= '?,';
                $insertVal[] = $v;
            }
        }
        $insertStr = rtrim($insertStr, ',') . ')';
        $rep = rtrim($rep, ',') . ')';
        $this->cqlSlice['insert'] = $insertStr . ' values ' . $rep;
        $this->paramSlice['insert'] = $insertVal;
        $this->resolve('insert');
        $this->execute(['arguments' => $this->params], $this->cql);
    }

    /**
     * @param array $insert
     * @return AsyncCqlResponse[]|array
     * @throws \Exception
     */
    public function multiInsert(array $insert)
    {
        $columns = array_keys($insert[0]);
        $insertStr = '(';
        $rep = '(';
        foreach ($columns as $k) {
            $insertStr .= $k . ',';
            $rep .= '?,';
        }
        $insertStr = rtrim($insertStr, ',') . ')';
        $rep = rtrim($rep, ',') . ')';
        $this->cqlSlice['insert'] = $insertStr . ' values ' . $rep;
        $this->paramSlice['insert'] = [];
        foreach ($insert as $row) {
            $this->paramSlice['insert'][] = array_values($row);
        }
        $this->resolve('insert');
        return $this->multiExec($this->params, $this->cql);
    }

    public function update($update)
    {
        if (!$update) return false;
        $upStr = '';
        $upVal = [];
        $allow = $this->paramSlice['allow'];
        $filter = ($allow !== []) ? true : false;
        foreach ($update as $k => $v) {
            if (!$filter || isset($allow[$k])) {
                $upStr .= $k . ' = ?,';
                $upVal[] = $v;
            }
        }
        $this->cqlSlice['update'] = rtrim($upStr, ',');
        $this->paramSlice['update'] = $upVal;
        $this->resolve('update');
        $this->execute(['arguments' => $this->params], $this->cql);
    }

    public function delete()
    {
        $this->resolve('delete');
        $this->execute(['arguments' => $this->params], $this->cql);
    }

    /**
     * @param array $where
     * @return AsyncCqlResponse[]|array
     * @throws \Exception
     */
    public function multiDelete(array $where)
    {
        $columns = array_keys($where[0]);
        $whereStr = ' WHERE ';
        foreach ($columns as $c) {
            $whereStr .= $c . ' = ? AND ';
        }
        $this->cqlSlice['where'] = substr($whereStr, 0, -4);
        $this->paramSlice['where'] = [];
        foreach ($where as $row) {
            $this->paramSlice['where'][] = array_values($row);
        }
        $this->resolve('delete');
        return $this->multiExec($this->params, $this->cql);
    }

    public function getCql()
    {
        $this->resolve();
        return $this->resolveCql();
    }

    protected function resolveCql()
    {
        if (!$this->cql) return '';
        $arr = explode('?', $this->cql);
        $cql = '';
        foreach ($arr as $k => $v) {
            $cql .= $v . ($this->params[$k] ?? '');
        }
        if (!$cql) $cql = $arr[0];
        return $cql;
    }

    public function get(\Closure $handler = null)
    {
        $this->resolve();
        $res = $this->execute(['arguments' => $this->params], $this->cql);
        if ($this->async) {
            return new AsyncCqlResponse($res, $handler);
        }
        $result = [];
        foreach ($res as $row) {
            $result[] = $handler ? $handler($row) : $row;
        }
        return $result;
    }

    public function first()
    {
        $this->resolve();
        $res = $this->execute(['arguments' => $this->params], $this->cql);
        if ($this->async) {
            return new AsyncCqlResponse($res);
        }
        return $res[0];
    }

    public function max($column)
    {
        $this->select("max($column) as num");
        $this->resolve();
        $res = $this->execute(['arguments' => $this->params], $this->cql);
        if ($this->async) {
            return new AsyncCqlResponse($res);
        }
        return $res[0]['num'];
    }

    public function min($column)
    {
        $this->select("min($column) as num");
        $this->resolve();
        $res = $this->execute(['arguments' => $this->params], $this->cql);
        if ($this->async) {
            return new AsyncCqlResponse($res);
        }
        return $res[0]['num'];
    }

    public function count()
    {
        $this->select('count(*) as num');
        $this->resolve();
        $res = $this->execute(['arguments' => $this->params], $this->cql);
        if ($this->async) {
            return new AsyncCqlResponse($res);
        }
        return $res[0]['num']->value();
    }

    public function avg($column)
    {
        $this->select("avg($column) as num");
        $this->resolve();
        $res = $this->execute(['arguments' => $this->params], $this->cql);
        if ($this->async) {
            return new AsyncCqlResponse($res);
        }
        return $res[0]['num'];
    }

    public function sum($column)
    {
        $this->select("sum($column) as num");
        $this->resolve();
        $res = $this->execute(['arguments' => $this->params], $this->cql);
        if ($this->async) {
            return new AsyncCqlResponse($res);
        }
        return $res[0]['num'];
    }

    public function value($column)
    {
        $this->select($column);
        $this->resolve();
        $res = $this->execute(['arguments' => $this->params], $this->cql);
        if ($this->async) {
            return new AsyncCqlResponse($res);
        }
        if ($res[0] === null) return null;
        return $res[0][$column];
    }

    /**
     * 不支持异步调用
     * @param $column
     * @param string $key
     * @param \Closure|null $handler
     * @return array
     * @throws \Exception
     */
    public function pluck($column, $key = '', \Closure $handler = null)
    {
        $columns = [$column];
        if ($key) $columns[] = $key;
        $this->select($columns);
        $this->resolve();
        $res = $this->execute(['arguments' => $this->params], $this->cql);
        $result = [];
        if ($key) {
            foreach ($res as $row) {
                $row = $handler ? $handler($row) : $row;
                $result[$row[$key]] = $row[$column];
            }
        } else {
            foreach ($res as $row) {
                $row = $handler ? $handler($row) : $row;
                $result[] = $row[$column];
            }
        }
        return $result;
    }

    public function prepare($cql)
    {
        try {
            $this->statement = $this->db->prepare($cql);
        } catch (\Exception $e) {
            $cql = $this->resolveCql();
            throw new \Exception($e->getMessage().' ; cql: ' . $cql);
        }
        return $this;
    }

    public function execute($option, $cql = '')
    {
        $res = [];
        $cql = $cql ? $cql : $this->statement;
        try {
            if ($this->batch) {
                // 批处理直接填充参数
                $this->batch->add($cql, $option['arguments']);
                $this->batch->cql[] = $cql;
            } else {
                if ($this->async) {
                    $res = $this->db->executeAsync($cql, $option);
                } else {
                    $res = $this->db->execute($cql, $option);
                }
            }
        } catch (\Exception $e) {
            $cql = $this->resolveCql();
            throw new \Exception($e->getMessage().' ; cql: ' . $cql);
        }
        return $res;
    }

    // 不支持异步操作
    public function multiExec($options, $cql = '')
    {
        $this->prepare($cql);
        $res = [];
        try {
            if ($this->batch) {
                foreach ($options as $op) {
                    $this->batch->add($this->statement, $op);
                    $this->batch->cql[] = $cql;
                }
            } else {
                foreach ($options as $op) {
                    if ($this->async) {
                        $r = $this->db->executeAsync($this->statement, ['arguments' => $op]);
                        $res[] = new AsyncCqlResponse($r);
                    } else {
                        $this->db->execute($this->statement, ['arguments' => $op]);
                    }

                }
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage().' ; cql: ' . $cql);
        }
        return $res;
    }

    public function setBatch($batch)
    {
        $this->batch = $batch;
        return $this;
    }

    public function async()
    {
        $this->async = true;
        return $this;
    }

    /**
     * 不支持异步
     * @param $pages
     * @param $size
     * @param string $flag
     * @param \Closure|null $handler
     * @return array
     * @throws \Exception
     */
    public function page($pages, $size, $flag = 'id', \Closure $handler = null)
    {
        $this->resolve();
        $offset = ($pages - 1) * $size;
        if ($offset <= 0) {
            $result = $this->execute(['arguments' => $this->params, 'page_size' => $size], $this->cql);
        } else {
            $cql = $this->pageCql($flag);
            $result = $this->execute(['arguments' => $this->params, 'page_size' => $offset], $cql);
            $pageToken = $result->pagingStateToken();
            if ($pageToken === null) return [];
            $result = $this->execute(['arguments' => $this->params, 'paging_state_token' => $result->pagingStateToken(), 'page_size' => $size], $this->cql);
        }
        $re = [];
        foreach ($result as $row) {
            $re[] = $handler ? $handler($row) : $row;
        }
        return $re;
    }

    private function pageCql($flag)
    {
        return 'SELECT ' . $flag . ' FROM ' . $this->cqlSlice['table'] . ' ' . $this->cqlSlice['where']
            . $this->cqlSlice['group_by'] . $this->cqlSlice['order_by'] . $this->cqlSlice['limit'];
    }

    private function resolve($option = '')
    {
        if (substr_count($this->cql, '?') !== count($this->params)) throw new \Exception('params ind error; cql: ' . $this->cql);
        if ($option === 'insert') {
            $this->cql = 'INSERT INTO ' . $this->cqlSlice['table'] . ' ' . $this->cqlSlice['insert'] . $this->cqlSlice['ttl'];
            $this->params = $this->paramSlice['insert'];
        } elseif ($option === 'update') {
            $this->cql = 'UPDATE ' . $this->cqlSlice['table'] . $this->cqlSlice['ttl'] . ' SET ' . $this->cqlSlice['update'] . ' ' . $this->cqlSlice['where'];
            $this->params = array_merge($this->paramSlice['update'], $this->paramSlice['where']);
        } elseif ($option === 'delete') {
            $this->cql = 'DELETE FROM ' . $this->cqlSlice['table'] . ' ' . $this->cqlSlice['where'];
            $this->params = $this->paramSlice['where'];
        } else {
            $columns = $this->cqlSlice['columns'] ? $this->cqlSlice['columns'] : '*';
            $this->cql = 'SELECT ' . $columns . ' FROM ' . $this->cqlSlice['table'] . ' ' . $this->cqlSlice['where']
            . $this->cqlSlice['group_by'] . $this->cqlSlice['order_by'] . $this->cqlSlice['limit'];
            $this->params = $this->paramSlice['where'];
        }
    }








}