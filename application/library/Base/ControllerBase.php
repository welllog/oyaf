<?php

namespace Base;

use Exc\UsrExc;
use Valitron\Validator;

class ControllerBase extends \Yaf\Controller_Abstract
{
    protected $_params;
    protected $_query;
    protected $_postForm;
    protected $_post;
    /** @var Validator */
    public $validator;

    public function init()
    {
        Validator::lang('zh-cn');
    }

    public function getQuery($name = '', $default = null) {
        if ($this->_query === null) {
            $this->_query = filterInput($this->_request->getQuery());
        }
        if ($name) {
            return $this->_query[$name] ?? $default;
        }
        return $this->_query;
    }

    public function getPostFrom($name = '', $default = null) {
        if ($this->_postForm === null) {
            $this->_postForm = filterInput($this->_request->getPost());
        }
        if ($name) {
            return $this->_postForm[$name] ?? $default;
        }
        return $this->_postForm;
    }

    public function getParams($name = '', $default = null) {
        if ($this->_params === null) {
            $this->_params = filterInput($this->_request->getParams());
        }
        if ($name) {
            return $this->_params[$name] ?? $default;
        }
        return $this->_params;
    }

    public function getPost($name = '', $default = null) {
        if ($this->_post === null) {
            $in = json_decode(file_get_contents('php://input'), 1);
            $this->_post = filterInput(is_array($in) ? $in : []);
        }
        if ($name) {
            return $this->_post[$name] ?? $default;
        }
        return $this->_post;
    }

    public function makeValidator(array $params = [])
    {
        $this->validator = new Validator($params);
        return $this;
    }

    public function rule(...$params)
    {
        $this->validator->rule(...$params);
        return $this;
    }

    public function tips($tip)
    {
        $this->validator->message($tip);
        return $this;
    }

    public function validate()
    {
        $valid = $this->validator->validate();
        if (!$valid) {
            $errors = $this->validator->errors();
            foreach ($errors as $v) {
                throw new UsrExc($v[0], UsrExc::PARAMS_EX);
            }
        }
    }

    public function ajaxReturn($code, $msg, $data, $meta = '', $httpCode = 200)
    {
        $this->_response->setBody(json_encode([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
            'meta' => $meta
        ], JSON_UNESCAPED_UNICODE));
        http_response_code($httpCode);
        header("Content-type: application/json;charset=utf-8");
        return false;
    }

    public function ajaxSuccess($data = '', $meta = '')
    {
        return $this->ajaxReturn(0, 'success', $data, $meta);
    }

    public function ajaxError($code, $msg, $httpCode = 200)
    {
        return $this->ajaxReturn($code, $msg, '', '', $httpCode);
    }
}