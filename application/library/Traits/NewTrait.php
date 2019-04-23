<?php
/**
 * Created by PhpStorm.
 * User: chentairen
 * Date: 2019/4/15
 * Time: 下午5:24
 */

namespace Traits;

trait NewTrait {

    public static function new(...$params)
    {
        return (new static(...$params));
    }

}