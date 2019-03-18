<?php

namespace Exc;

/**
 * 仅用于抛出系统异常日志
 * Class SysExc
 * @package Exc
 */
class SysExc extends BaseException
{
    const COMMON_EX = 1000;

    public static $_exMap = [
        self::COMMON_EX => '服务异常',
    ];

    public function __construct($msg, $code = self::COMMON_EX)
    {
        parent::__construct($msg, $code);
    }
}