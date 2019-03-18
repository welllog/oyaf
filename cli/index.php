<?php
define('APPLICATION_PATH', dirname(__DIR__));

require APPLICATION_PATH . "/conf/app.php";
require APPLICATION_PATH . "/vendor/autoload.php";

$application = new Yaf\Application( APPLICATION_PATH . "/conf/application.ini", RUN_MODE);

$application->bootstrap()->getDispatcher()->dispatch(new Yaf\Request\Simple());
?>
