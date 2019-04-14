<?php

namespace Olog;

class FileOut implements Output
{
    protected $logPath;
    protected $multiMsg = '';

    public function __construct($logPath)
    {
        if (!file_exists($logPath)) {
            mkdir($logPath, 0777, true);
        }
        $this->logPath = $logPath;
    }

    public function write($level, $logid, array $option, $message, int $time)
    {
        $this->multiMsg .= "[$level][".date('Y-m-d H:i:s', $time)."][$logid]";
        foreach ($option as $k => $v) {
            $this->multiMsg .= "$k: $v; ";
        }
        $this->multiMsg .= 'message: ' . $message . PHP_EOL;
    }

    public function realWrite()
    {
        $logPath = $this->logPath . '/app_' . date('Y-m-d') . '.log';
        // warning错误，无法捕获异常
        if (!$this->multiMsg) return;
        @file_put_contents($logPath, $this->multiMsg, FILE_APPEND);
        $this->multiMsg = '';
    }
}