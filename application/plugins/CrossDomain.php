<?php

class CrossDomainPlugin extends Yaf\Plugin_Abstract {
    // 路由之前
	public function routerStartup(Yaf\Request_Abstract $request, Yaf\Response_Abstract $response) {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: GET,POST,PATCH,PUT,OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type,Origin,Cookie,Accept");
        header("Access-Control-Expose-Headers: ");
        if ($request->isOptions()) exit;
	}
    // 分发循环结束，所有业务逻辑已经完成，响应还未发送
	public function dispatchLoopShutdown(Yaf\Request_Abstract $request, Yaf\Response_Abstract $response) {
	}
}
