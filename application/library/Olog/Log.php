<?php

namespace Olog;

use Psr\Log\LoggerInterface;

class Log
{
    protected static $logger;

    /**
     * 仅限初始化时调用
     * @param string $level
     * @param Output[] $output
     * @return null|Logger
     */
    public static function init($level, Output ...$output) : LoggerInterface
    {
        if (null === self::$logger) {
            self::$logger = new Logger($level, ...$output);
        }
        return self::$logger;
    }

    // 获取实现psr-3标准的日志对象
    public static function get() : LoggerInterface
    {
        if (null === self::$logger) {
            throw new \Exception('please instance log');
        }
        return self::$logger;
    }

    // 设置k-v，如['userid' => 1]
    public static function set(array $option)
    {
        self::get()->set($option);
    }

    /**
     * @param Output $output
     * @throws \Exception
     */
    public static function setOutput(Output $output)
    {
        self::get()->setOutput($output);
    }

    /**
     * @param string $message
     * @param array $context
     * @throws \Exception
     */
    public static function emergency($message, array $context = [])
    {
        self::get()->emergency($message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     * @throws \Exception
     */
    public static function alert($message, array $context = [])
    {
        self::get()->alert($message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     * @throws \Exception
     */
    public static function critical($message, array $context = [])
    {
        self::get()->critical($message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     * @throws \Exception
     */
    public static function error($message, array $context = [])
    {
        self::get()->error($message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     * @throws \Exception
     */
    public static function warning($message, array $context = [])
    {
        self::get()->warning($message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     * @throws \Exception
     */
    public static function notice($message, array $context = [])
    {
        self::get()->notice($message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     * @throws \Exception
     */
    public static function info($message, array $context = [])
    {
        self::get()->info($message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     * @throws \Exception
     */
    public static function debug($message, array $context = [])
    {
        self::get()->debug($message, $context);
    }

    public static function flush()
    {
        self::get()->flush();
    }
}