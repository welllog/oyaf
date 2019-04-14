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

    public function __construct($clientClass, $address)
    {
        $this->client = new $clientClass($address, [
            'credentials' => ChannelCredentials::createInsecure(),
            'timeout' => $this->timeOut * 1000
        ]);
    }

    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    public function exec($method)
    {
        return $this->client->$method($this->request)->wait();
    }

    public function call($method)
    {
        for ($i = 0; $i < $this->maxTry + 1; ++$i) {
            list($reply, $status) = $this->exec($method);

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