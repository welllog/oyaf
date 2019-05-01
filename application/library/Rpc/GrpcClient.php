<?php
/**
 * Created by PhpStorm.
 * User: chentairen
 * Date: 2019/4/14
 * Time: 下午2:07
 */

namespace Rpc;

use Grpc\ChannelCredentials;
use const Grpc\STATUS_OK;
use const Grpc\STATUS_UNAVAILABLE;

class GrpcClient
{
    /** @var int ms */
    protected $timeOut = 500;
    protected $maxTry = 1;
    protected $client;
    protected $request;
    /** @var int ms */
    protected $waitForChannelReady = 300;

    /**
     * GrpcClient constructor.
     * @param $clientClass
     * @param $address
     */
    public function __construct($clientClass, $address)
    {
        $this->client = new $clientClass($address, [
            'credentials' => ChannelCredentials::createInsecure(),
            'timeout' => $this->timeOut * 1000
        ]);
    }

    /**
     * @param $request
     * @return $this
     */
    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * 返回异步执行对象
     * @param $method
     * @return GrpcAsync
     */
    public function ascyncExec($method)
    {
        $future = $this->client->$method($this->request);
        return (new GrpcAsync($this->client, $future, $this->request, $method));
    }

    /**
     * 同步执行
     * @param $method
     * @return GrpcResponse
     */
    public function call($method)
    {
        for ($i = 0; $i < $this->maxTry + 1; ++$i) {
            list($reply, $status) = $this->client->$method($this->request)->wait();

            if ($status->code === STATUS_UNAVAILABLE) { // 仅对该错误码进行重试,其余状态都跳出循环
                $this->client->waitForReady($this->waitForChannelReady);
                continue;
            }

            break;

        }

        if ($status->code === STATUS_OK) {
            return new GrpcResponse(STATUS_OK, '', $reply);
        }

        return new GrpcResponse($status->code, $status->details);

    }

}