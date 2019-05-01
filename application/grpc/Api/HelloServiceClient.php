<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Api;

/**
 */
class HelloServiceClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Api\Hello $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     */
    public function SayHello(\Api\Hello $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/api.HelloService/SayHello',
        $argument,
        ['\Api\Hello', 'decode'],
        $metadata, $options);
    }

}
