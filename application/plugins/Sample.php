<?php
/**
 * @name SamplePlugin
 * @desc Yaf定义了如下的6个Hook,插件之间的执行顺序是先进先Call
 * @see http://www.php.net/manual/en/class.yaf-plugin-abstract.php
 * @author chentairen
 */

use Olog\Log;

class SamplePlugin extends Yaf\Plugin_Abstract {
    // 路由之前
	public function routerStartup(Yaf\Request_Abstract $request, Yaf\Response_Abstract $response) {
	}
    // 路由结束之后
	public function routerShutdown(Yaf\Request_Abstract $request, Yaf\Response_Abstract $response) {
	}
    // 分发循环开始之前
	public function dispatchLoopStartup(Yaf\Request_Abstract $request, Yaf\Response_Abstract $response) {
	}
    // 分发之前，forward可能触发多次,业务代码之前
	public function preDispatch(Yaf\Request_Abstract $request, Yaf\Response_Abstract $response) {
	}
    // 分发之后，forward可能触发多次，业务代码之后
	public function postDispatch(Yaf\Request_Abstract $request, Yaf\Response_Abstract $response) {
	}
    // 分发循环结束，所有业务逻辑已经完成，响应还未发送
	public function dispatchLoopShutdown(Yaf\Request_Abstract $request, Yaf\Response_Abstract $response) {
	    Log::realWrite();
	}
}
