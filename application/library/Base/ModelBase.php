<?php
/**
 * Created by PhpStorm.
 * User: chentairen
 * Date: 2019/3/18
 * Time: 上午11:49
 */

namespace Base;

use Odb\DB;
use Odb\SqlBuilder;

class ModelBase
{
    protected $table;
    protected $primaryKey = 'id';

    protected function getTable()
    {
        if ($this->table === null) {
            $table = lcfirst(substr(get_class($this), 0, -5));
            $table = preg_replace('/([A-Z])/', '_\\1', $table);
            $this->table = strtolower($table);
        }
        return $this->table;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function all()
    {
        $table = $this->getTable();
        return DB::table($table)->get();
    }

    /**
     * @param $id
     * @return array|null
     * @throws \Exception
     */
    public function find($id)
    {
        $table = $this->getTable();
        return DB::table($table)->where($this->primaryKey, $id)->first();
    }

    /**
     * @param array ...$params
     * @return SqlBuilder
     * @throws \Exception
     */
    public function where(...$params)
    {
        $table = $this->getTable();
        return DB::table($table)->where(...$params);
    }

    /**
     * @return SqlBuilder
     * @throws \Exception
     */
    public function useBuild()
    {
        $table = $this->getTable();
        return DB::table($table);
    }
}