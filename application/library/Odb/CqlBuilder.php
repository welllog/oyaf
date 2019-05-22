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
    /** @var CqlOperator */
    protected $op;
    protected $cql;
    protected $params = [];

    protected $cqlSlice = [
        'columns' => '', 'table' => '', 'ttl' => '', 'where' => '', 'group_by' => '', 'order_by' => '',
        'limit' => '', 'update' => ''
    ];

    protected $paramSlice = [
        'allow' => [], 'where' => [], 'update' => []
    ];

    public static function new()
    {
        return new static();
    }

    public function setOperator(CqlOperator $op)
    {
        $this->op = $op;
        return $this;
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

    public function buildInsert($insert)
    {
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
        $insertStr = $insertStr . ' values ' . $rep;
        $this->cql = 'INSERT INTO ' . $this->cqlSlice['table'] . ' ' . $insertStr . $this->cqlSlice['ttl'];
        $this->params = $insertVal;
        $this->clean();
        return $this;
    }

    protected function _buildUpdate()
    {
        $this->cql = 'UPDATE ' . $this->cqlSlice['table'] . $this->cqlSlice['ttl'] . ' SET ' . $this->cqlSlice['update'] . ' ' . $this->cqlSlice['where'];
        $this->params = array_merge($this->paramSlice['update'], $this->paramSlice['where']);
        $this->clean();
    }

    public function buildUpdate($update)
    {
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
        $this->_buildUpdate();
        return $this;
    }

    public function buildIncrement($column, $step)
    {
        $step = intval($step);
        $this->cqlSlice['update'] = $column . ' = ' . $column . ' + ' . $step;
        $this->_buildUpdate();
        return $this;
    }

    public function buildDelete()
    {
        $this->cql = 'DELETE FROM ' . $this->cqlSlice['table'] . ' ' . $this->cqlSlice['where'];
        $this->params = $this->paramSlice['where'];
        $this->clean();
        return $this;
    }

    public function buildQuery()
    {
        $columns = $this->cqlSlice['columns'] ? $this->cqlSlice['columns'] : '*';
        $this->cql = 'SELECT ' . $columns . ' FROM ' . $this->cqlSlice['table'] . ' ' . $this->cqlSlice['where']
            . $this->cqlSlice['group_by'] . $this->cqlSlice['order_by'] . $this->cqlSlice['limit'];
        $this->params = $this->paramSlice['where'];
        $this->clean();
        return $this;
    }

    /**
     * @param $insert
     * @return null|AsyncCqlResponse
     * @throws \Exception
     */
    public function insert($insert)
    {
        if (!$insert) return null;
        $this->buildInsert($insert);
        return $this->op->execute($this->params, $this->cql)->getResult();
    }

    /**
     * @param array $insert
     * @return AsyncCqlResponse[]|array
     * @throws \Exception
     */
    public function multiInsert(array $insert)
    {
        $this->buildInsert($insert[0]);
        $this->op->prepare($this->cql);

        $resp = [];
        foreach ($insert as $row) {
            $params = array_values($row);
            $resp[] = $this->op->execute($params)->getResult();
        }
        return $resp;
    }

    /**
     * @param $update
     * @return null|AsyncCqlResponse
     * @throws \Exception
     */
    public function update($update)
    {
        if (!$update) return null;
        $this->buildUpdate($update);
        return $this->op->execute($this->params, $this->cql)->getResult();
    }

    /**
     * @param $column
     * @param $step
     * @return AsyncCqlResponse
     * @throws \Exception
     */
    public function increment($column, $step)
    {
        $this->buildIncrement($column, $step);
        return $this->op->execute($this->params, $this->cql)->getResult();
    }

    /**
     * @return AsyncCqlResponse
     * @throws \Exception
     */
    public function delete()
    {
        $this->buildDelete();
        return $this->op->execute($this->params, $this->cql)->getResult();
    }

    public function getCql()
    {
        return $this->cql;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function getRcql()
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

    /**
     * handler需要注意值为空
     * @param \Closure|null $handler
     * @return array|AsyncCqlResponse
     * @throws \Exception
     */
    public function get(\Closure $handler = null)
    {
        $this->buildQuery();
        return $this->op->execute($this->params, $this->cql)->get($handler);
    }

    /**
     * handler需要注意值为空
     * @param \Closure|null $handler
     * @return mixed|null|AsyncCqlResponse
     * @throws \Exception
     */
    public function first(\Closure $handler = null)
    {
        $this->buildQuery();
        return $this->op->execute($this->params, $this->cql)->first($handler);
    }

    /**
     * @param $column
     * @return mixed|null|AsyncCqlResponse
     * @throws \Exception
     */
    public function max($column)
    {
        $this->select("max($column) as num")->buildQuery();
        $res = $this->op->execute($this->params, $this->cql)->getResult();
        if ($res instanceof AsyncCqlResponse) return $res;
        return $res[0]['num'];
    }

    /**
     * @param $column
     * @return mixed|null|AsyncCqlResponse
     * @throws \Exception
     */
    public function min($column)
    {
        $this->select("min($column) as num")->buildQuery();
        $res = $this->op->execute($this->params, $this->cql)->getResult();
        if ($res instanceof AsyncCqlResponse) return $res;
        return $res[0]['num'];
    }

    /**
     * @return mixed|null|AsyncCqlResponse
     * @throws \Exception
     */
    public function count()
    {
        $this->select('count(*) as num')->buildQuery();
        $res = $this->op->execute($this->params, $this->cql)->getResult();
        if ($res instanceof AsyncCqlResponse) return $res;
        return $res[0]['num']->value();
    }

    /**
     * @param $column
     * @return mixed|null|AsyncCqlResponse
     * @throws \Exception
     */
    public function avg($column)
    {
        $this->select("avg($column) as num")->buildQuery();
        $res = $this->op->execute($this->params, $this->cql)->getResult();
        if ($res instanceof AsyncCqlResponse) return $res;
        return $res[0]['num'];
    }

    /**
     * @param $column
     * @return mixed|null|AsyncCqlResponse
     * @throws \Exception
     */
    public function sum($column)
    {
        $this->select("sum($column) as num")->buildQuery();
        $res = $this->op->execute($this->params, $this->cql)->getResult();
        if ($res instanceof AsyncCqlResponse) return $res;
        return $res[0]['num'];
    }

    /**
     * @param $column
     * @param \Closure|null $handler
     * @return mixed|null|AsyncCqlResponse
     * @throws \Exception
     */
    public function value($column, \Closure $handler = null)
    {
        $this->select($column);
        $this->buildQuery();
        return $this->op->execute($this->params, $this->cql)->value($column, $handler);
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
        $this->buildQuery();
        return $this->op->execute($this->params, $this->cql)->pluck($column, $key, $handler);
    }

    public function async()
    {
        $this->op->async();
        return $this;
    }

    public function getOperator()
    {
        return $this->op;
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
        $this->buildQuery();
        $offset = ($pages - 1) * $size;
        if ($offset <= 0) {
            $result = $this->op->origExecute(['arguments' => $this->params, 'page_size' => $size], $this->cql)->getResult();
        } else {
            $cql = $this->pageCql($flag);
            $result = $this->op->origExecute(['arguments' => $this->params, 'page_size' => $offset], $cql)->getResult();
            $pageToken = $result->pagingStateToken();
            if ($pageToken === null) return [];
            $result = $this->op->origExecute(['arguments' => $this->params, 'paging_state_token' => $result->pagingStateToken(), 'page_size' => $size], $this->cql)
                ->getResult();
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

    protected function clean()
    {
        foreach ($this->cqlSlice as $ck => $cv) {
            $this->cqlSlice[$ck] = '';
        }
        foreach ($this->paramSlice as $pk => $pv) {
            $this->paramSlice[$pk] = [];
        }
    }

}