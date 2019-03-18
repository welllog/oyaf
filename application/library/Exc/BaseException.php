<?php

namespace Exc;

class BaseException extends \Exception
{
    public static $_exMap = [];

    // 自定义异常，为_exMap中的异常
    protected $prettyMsg = '';

    // $msg传入会被认为异常信息,prettyMsg为声明的exMap中的,exMap中不存在时,$msg认为异常信息和prettyMsg
    public function __construct($msg, $code)
    {
        $this->code = $code;
        $this->prettyMsg = empty(static::$_exMap[$code]) ? $msg : static::$_exMap[$code];
        $this->message = $msg ?: $this->prettyMsg;
    }

    public function getPrettyMessage()
    {
        return $this->prettyMsg;
    }
}