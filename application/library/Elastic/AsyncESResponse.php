<?php
/**
 * Created by PhpStorm.
 * User: chentairen
 * Date: 2019/5/1
 * Time: 下午11:44
 */

namespace Elastic;


class AsyncESResponse
{
    protected $future;
    protected $handler;

    public function __construct($future, \Closure $handler = null)
    {
        $this->future = $future;
        $this->handler = $handler;
    }

    public function wait()
    {
        if ($this->handler) {
            return ($this->handler)($this->future);
        }
        return $this->future->wait();
    }
}