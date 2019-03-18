<?php

namespace Olog;


use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

class ESOut implements Output
{
    /** @var Client */
    protected $escli;
    protected $index;
    protected $available = true;
    protected $logs = [];

    public function __construct($indexName, ...$host)
    {
        try {
            // php客户端采用resetful api,可以不需要客户端单例模式
            $this->escli = ClientBuilder::create()
                ->setHosts($host)
                ->build();

            $this->index = $indexName;

            $indices = $this->escli->indices();
            $exsists = $indices->exists(['index' => $indexName]);
            if (!$exsists) { // 创建索引
                $indices->create([
                    'index' => $indexName,
                    'body' => [
                        'settings' => [
                            'number_of_replicas' => 0,
                        ],
                        'mappings' => [
                            $indexName => [
                                'dynamic' => false,
                                'properties' => [
                                    'Level' => ['type' => 'keyword', 'ignore_above' => 256],
                                    'Logid' => ['type' => 'keyword', 'ignore_above' => 256],
                                    'Option' => [
                                        'dynamic' => true,
                                        'properties' => []
                                    ],
                                    'Message' => ['type' => 'keyword', 'index' => false],
                                    '@timestamp' => [
                                        'type' => 'date',
                                        'format' => 'yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_second'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]);
            }
        } catch (\Exception $e) {
            $this->available = false;
        }

    }

    public function write($level, $logid, array $option ,$message)
    {
        if (!$this->available) return;
        $this->logs[] = [
            'Level' => $level,
            'Logid' => $logid,
            'Option' => $option,
            'Message' => $message,
            '@timestamp' => time()
        ];
    }

    public function realWrite()
    {
        if (!$this->available) return;
        $data['body'] = [];
        foreach ($this->logs as $row) {
            $data['body'][] = [
                'index' => [
                    '_index' => $this->index,
                    '_type' => $this->index,
                ]
            ];
            $data['body'][] = $row;
        }
        if (!$data['body']) return;
        return $this->escli->bulk($data);
    }
}