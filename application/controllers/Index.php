<?php
/**
 * @name IndexController
 * @author chentairen
 * @desc 默认控制器
 * @see http://www.php.net/manual/en/class.yaf-controller-abstract.php
 */

use Olog\Log;
use Ocache\Cache;
use Odb\CDB;

class IndexController extends \Base\ControllerBase {

	/**
     * 默认动作
     * Yaf支持直接把Yaf\Request\Abstract::getParam()得到的同名参数作为Action的形参
     * 对于如下的例子, 当访问http://yourhost/oyaf/index/index/index/name/chentairen 的时候, 你就会发现不同
     */
	public function indexAction($name = "Stranger") {
		//1. fetch query
		$get = $this->getRequest()->getQuery("get", "default value");

		//2. fetch model
		$model = new SampleModel();

		//3. assign
		$this->getView()->assign("content", $model->selectSample());
		$this->getView()->assign("name", $name);

		//4. render by Yaf, 如果这里返回FALSE, Yaf将不会调用自动视图引擎Render模板
        return TRUE;
	}

    public function demoAction()
    {
        $post = $this->getPost();
        $this->makeValidator($post)->rule('required', 'name')
            ->validate();

        Log::debug(json_encode($post));

        $user = (new UserModel())->buildQuery()->where('name', $post['name'])->first();
        return $this->ajaxSuccess($user);
	}

    public function ybAction()
    {
        $yt = new YouTubeDownloader();

        $links = $yt->getDownloadLinks("https://www.youtube.com/watch?v=BuPM1wys0lI");

        var_dump($links);exit;
	}
}
