<?php
/**
 * @name Bootstrap
 * @author welllog
 * @desc 所有在Bootstrap类中, 以_init开头的方法, 都会被Yaf调用,
 * @see http://www.php.net/manual/en/class.yaf-bootstrap-abstract.php
 * 这些方法, 都接受一个参数:Yaf\Dispatcher $dispatcher
 * 调用的次序, 和申明的次序相同
 */

use Enum\RgtEnum;
use Olog\Log;
use Olog\FileOut;
use Ocache\RedisCache;
use Ocache\FileCache;
use Ocache\Cache;

/**
 * 该类应防止异常或抛错
 * Class Bootstrap
 */
class Bootstrap extends Yaf\Bootstrap_Abstract{

    public function _initConfig() {
		//把配置保存起来
		$arrConfig = Yaf\Application::app()->getConfig();
        Yaf\Registry::set(RgtEnum::FRAME_CONF, $arrConfig);
        $dbconf = include APPLICATION_PATH . '/conf/dbconf.php';
        \Yaf\Registry::set(RgtEnum::DB_CONF, $dbconf[RUN_MODE]);

        // 注册日志,默认文件缓存
        Log::instance($arrConfig['loglevel'], new FileOut(APPLICATION_PATH . '/storage/logs'));

        if ($arrConfig['cachedriver'] == 'redis') {
            $cacher = new RedisCache($dbconf[RUN_MODE]['redis']['cache']);
        } elseif ($arrConfig['cachedriver'] == 'file') {
            $cacher = new FileCache(APPLICATION_PATH . '/storage/cache', 86400 * 5);
        }
        // 注册缓存
        Cache::instance($cacher);
	}

    public function _initErrorHandle()
    {
        // 普通异常跟致命错误已被框架捕获，此处只需要捕获notice等警告错误
        set_error_handler(function($errno, $errstr, $errfile, $errline){
            if (!(error_reporting() & $errno)) {
                // error_reporting指令没有设置这个错误，所以将其忽略
                return;
            }
            throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
        });
	}

    public function _initFiles()
    {
        Yaf\Loader::import(APPLICATION_PATH . '/application/library/Util/Function.php');
	}

	public function _initPlugin(Yaf\Dispatcher $dispatcher) {
		//注册一个插件
		$objSamplePlugin = new SamplePlugin();
		$dispatcher->registerPlugin($objSamplePlugin);
	}

	public function _initRoute(Yaf\Dispatcher $dispatcher) {
		//在这里注册自己的路由协议,默认使用简单路由
	}

	public function _initView(Yaf\Dispatcher $dispatcher){
		//在这里注册自己的view控制器，例如smarty,firekylin
	}
}
