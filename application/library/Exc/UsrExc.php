<?php

namespace Exc;

class UsrExc extends BaseException
{
    const COMMON_EX = 300;
    const PARAMS_EX = 301;  // 参数异常

    public static $_exMap = [

    ];

    public function __construct($msg, $code = self::COMMON_EX)
    {
        parent::__construct($msg, $code);
    }
}