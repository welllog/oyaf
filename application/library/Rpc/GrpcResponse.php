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
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return mixed
     */
    public function getErr()
    {
        return $this->err;
    }

    /**
     * @return null
     */
    public function getReply()
    {
        return $this->reply;
    }

    /**
     * @return array
     */
    public function getReplyArr()
    {
        if (!$this->reply) return [];
        return json_decode($this->reply->serializeToJsonString(), 1);
    }
}