<?php
namespace Traits;

trait SingletonTrait {
    protected static $instance = null;

    public static function getInstance(...$params) {
        if (null === static::$instance) {
            static::$instance = new static(...$params);
        }
        return static::$instance;
    }

}