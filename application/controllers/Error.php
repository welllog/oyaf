<?php
/**
 * @name ErrorController
 * @desc 错误控制器, 在发生未捕获的异常时刻被调用
 * @see http://www.php.net/manual/en/yaf-dispatcher.catchexception.php
 * @author chentairen
 */

use Exc\SysExc;
use Exc\BaseException;
use Olog\Log;

class ErrorController extends \Base\ControllerBase {

	//从2.1开始, errorAction支持直接通过参数获取异常
	public function errorAction($exception) {
        $code = $exception->getCode();
        $msg = $exception->getMessage();
        // 判断是否为自定义异常
        $position = ' in file: (' . $exception->getFile() . ') on line ' .  $exception->getLine();
//        $isAjax = $this->_request->isXmlHttpRequest();
        $isAjax = true;

	    if ($exception instanceof SysExc) { // 自定义系统异常
            $pmsg = $exception->getPrettyMessage();
            // 打印错误日志
	        Log::error("errcode: $code, err: $pmsg, detail: $msg, position: $position;");
        } elseif ($exception instanceof BaseException) { // 其它自定义异常,默认为逻辑异常
            // 打印调试日志
            Log::debug("uerrcode: $code, uerr: $msg, position: $position;");
            // ajax直接返回逻辑错误
            if ($isAjax) return $this->ajaxError($code, $msg);
        } else { // 其它异常默认为系统严重异常
            Log::error("errcode: $code, detail: $msg, position: $position;");
        }
	    if ($isAjax) {
            if (RUN_MODE === 'product') { // 返回友好错误
                return $this->ajaxError(SysExc::COMMON_EX, SysExc::$_exMap[SysExc::COMMON_EX]);
            } else { // 返回真实错误方便调试
                return $this->ajaxError(SysExc::COMMON_EX, $msg);
            }
        }
        // 非ajax请求默认处理
		//1. assign to view engine
		$this->getView()->assign("exception", $exception);
		//5. render by Yaf
	}
}
