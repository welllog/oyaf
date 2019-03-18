<?php

namespace Olog;

use Psr\Log\LoggerInterface;

class Logger implements LoggerInterface
{
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const NOTICE = 'NOTICE';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    const CRITICAL = 'CRITICAL';
    const ALERT = 'ALERT';
    const EMERGENCY = 'EMERGENCY';

    protected $level;
    protected $logid;
    protected $option = [];
    protected $outPut; /** @var Output[] **/

    protected $levels = array(
        self::EMERGENCY => 800,
        self::ALERT     => 700,
        self::CRITICAL  => 600,
        self::ERROR     => 500,
        self::WARNING   => 400,
        self::NOTICE    => 300,
        self::INFO      => 200,
        self::DEBUG     => 100,
    );

    /**
     * Logger constructor.
     * @param string $level
     * @param Output[] ...$output 输出者，需要实现Output接口
     */
    public function __construct($level = self::DEBUG, Output ...$output)
    {
        $this->setLevel($level);
        $this->setLogId();
        $this->outPut = $output;
    }

    /**
     * @param array $option
     */
    public function set(array $option)
    {
        $this->option = array_merge($this->option, $option);
    }

    /**
     * @param Output $output
     */
    public function setOutput(Output $output)
    {
        $this->outPut[] = $output;
    }

    /**
     * @param string $level
     */
    public function setLevel($level)
    {
        $this->level = isset($this->levels[$level]) ? $level : self::DEBUG;
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function emergency($message, array $context = [])
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function alert($message, array $context = [])
    {
       $this->log(self::ALERT, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function critical($message, array $context = [])
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function error($message, array $context = [])
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function warning($message, array $context = [])
    {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function notice($message, array $context = [])
    {
        $this->log(self::NOTICE, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function info($message, array $context = [])
    {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function debug($message, array $context = [])
    {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, array $context = [])
    {
        if ($this->levels[$this->level] > $this->levels[$level]) {
            return;
        }
        if ($context) {
            $message = $this->interpolate($message, $context);
        }
        foreach ($this->outPut as $output) {
            $output->write($level, $this->logid, $this->option, $message);
        }
    }

    public function realWrite()
    {
        foreach ($this->outPut as $output) {
            $output->realWrite();
        }
    }

    /**
     * @param string $message
     * @param array $context
     * @return string
     */
    protected function interpolate($message, array $context = [])
    {
        // 构建一个花括号包含的键名的替换数组
        $replace = [];
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }

        // 替换记录信息中的占位符，最后返回修改后的记录信息。
        return strtr($message, $replace);
    }

    protected function setLogId() {
        $this->logid = ((microtime(true) * 100000) % 2147483647);
//        $this->logid = md5(uniqid(mt_rand(), true));
    }

}