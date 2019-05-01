<?php
/**
 * Created by PhpStorm.
 * User: chentairen
 * Date: 2019/4/14
 * Time: 下午4:35
 */

namespace Rpc;


class GrpcResponse
{
    protected $code;
    protected $err;
    protected $reply;

    public function __construct($code, $err, $reply = null)
    {
        $this->code = $code;
        $this->err = $err;
        $this->reply = $reply;
    }

    /**
     * 返回grpc调用code
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * 获取grpc调用错误
     * @return mixed
     */
    public function getErr()
    {
        return $this->err;
    }

    /**
     * 返回grpc的结果对象，再通过类似getName 获取name属性名称
     * @return null
     */
    public function getReply()
    {
        return $this->reply;
    }

    /**
     * 返回结果数组
     * @return array
     */
    public function getReplyArr()
    {
        if (!$this->reply) return [];
        return json_decode($this->reply->serializeToJsonString(), 1);
    }
}