<?php
// 仅定义常量，防止出错，运行出错

define('REQ_BEGIN_TIME', microtime(true));

$mode = getenv('RUN_MODE');
if ($mode) {
    define('RUN_MODE', $mode); // product, dev
} else {
    define('RUN_MODE', 'dev'); // product, dev
}
