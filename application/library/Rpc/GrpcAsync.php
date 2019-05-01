<?php
/**
 * Created by PhpStorm.
 * User: chentairen
 * Date: 2019/4/27
 * Time: 下午6:01
 */

namespace Rpc;

// grpc异步执行的封装
use const Grpc\STATUS_OK;
use const Grpc\STATUS_UNAVAILABLE;

class GrpcAsync
{
    protected $client;
    protected $future;
    protected $request;
    protected $method;
    protected $maxTry = 1;

    public function __construct($client, $future, $request, $method)
    {
        $this->client = $client;
        $this->future = $future;
        $this->request = $request;
        $this->method = $method;
    }

    public function wait()
    {
        list($reply, $status) = $this->future->wait();
        $this->future = null;

        $times = 0;
        while ($status->code === STATUS_UNAVAILABLE && $times < $this->maxTry) {
            $this->client->waitForReady($this->waitForChannelReady);
            $method = $this->method;
            list($reply, $status) = $this->client->$method($this->request)->wait();

            ++$times;
        }

        $this->request = null;
        $this->client = null;

        if ($status->code === STATUS_OK) {
            return new GrpcResponse(STATUS_OK, '', $reply);
        }

        return new GrpcResponse($status->code, $status->details);
    }
}