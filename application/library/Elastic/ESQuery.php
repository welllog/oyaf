<?php
/**
 * Created by PhpStorm.
 * User: chentairen
 * Date: 2019/5/2
 * Time: 上午12:50
 */

namespace Elastic;


class ESQuery
{
    protected $index;
    protected $body = [];

    public static function new()
    {
        return (new static());
    }

    public function setIndex($index)
    {
        $this->index = $index;
        return $this;
    }

    /**
     * @param $must [['term' => ['col' => 'val']], ['ids' => ['values' => $arr]]]
     * @return $this
     */
    public function setMust($must)
    {
        $this->body['query']['bool']['must'] = $must;
        return $this;
    }

    /**
     * @param $mustnot [['term' => ['col' => 'val']]]
     * @return $this
     */
    public function setMustNot($mustnot)
    {
        $this->body['query']['bool']['must_not'] = $mustnot;
        return $this;
    }

    /**
     * @param $should [['term' => ['col' => 'val']]]
     * @return $this
     */
    public function setShould($should)
    {
        $this->body['query']['bool']['should'] = $should;
        return $this;
    }

    /**
     * @param $filters [['term' => ['col' => 'val']]]
     * @return $this
     */
    public function setFilter($filters)
    {
        $this->body['query']['bool']['filter'] = $filters;
        return $this;
    }

    public function setFilterMust($must)
    {
        $this->body['query']['bool']['filter']['bool']['must'] = $must;
        return $this;
    }

    public function setFilterMustNot($mustnot)
    {
        $this->body['query']['bool']['filter']['bool']['must_not'] = $mustnot;
        return $this;
    }

    public function setFilterShould($should)
    {
        $this->body['query']['bool']['filter']['bool']['should'] = $should;
        return $this;
    }

    public function setPage($from, $size)
    {
        $this->body['from'] = $from;
        $this->body['size'] = $size;
        return $this;
    }

    public function setFields($columns)
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $this->body['_source']['includes'] = $columns;
        return $this;
    }

    public function setNotFileds($columns)
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $this->body['_source']['excludes'] = $columns;
        return $this;
    }

    /**
     * @param $column
     * @param string $order asc|desc
     * @return $this
     */
    public function setOrder($column, $order = 'asc')
    {
        $this->body['sort'][] = [$column => ['order' => $order]];
        return $this;
    }

    public function build()
    {
        $query = [
            'index' => $this->index,
            'type' => $this->index,
            'body' => $this->body
        ];
        $this->body = [];
        return $query;
    }
}