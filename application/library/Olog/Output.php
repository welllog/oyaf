<?php

namespace Olog;

interface Output
{
    /**
     * 必须被实现
     * @param string $level
     * @param string $logid
     * @param array $option
     * @param string $message
     * @return mixed
     */
    public function write($level, $logid, array $option, $message);

    /**
     * 如果write方法已经实现记录日志而不是暂时缓存，此方法不需要被具体实现
     * @return mixed
     */
    public function realWrite();
}