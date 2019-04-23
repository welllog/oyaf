<?php

namespace Olog;


use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

class ESOut implements Output
{
    /** @var Client */
    protected $escli;
    protected $index;
    protected $initialize = false;
    protected $logs = [];

    public function __construct($indexName, ...$host)
    {
        // php客户端采用resetful api,可以不需要客户端单例模式
        $this->escli = ClientBuilder::create()
            ->setHosts($host)
            ->build();

        $this->index = $indexName;
    }

    public function initialize()
    {
        if ($this->initialize) return;
        try {
            $indices = $this->escli->indices();
            $exsists = $indices->exists(['index' => $this->index]);
            if (!$exsists) { // 创建索引
                $indices->create([
                    'index' => $this->index,
                    'body' => [
                        'settings' => [
                            'number_of_replicas' => 0,
                        ],
                        'mappings' => [
                            $this->index => [
                                'dynamic' => false,  // 不能动态添加字段
                                'properties' => [
                                    'Level' => ['type' => 'keyword', 'ignore_above' => 256, 'norms' => false], // 忽略评分因子
                                    'Logid' => ['type' => 'keyword', 'ignore_above' => 256, 'norms' => false],
                                    'Option' => [
                                        'dynamic' => true,
                                        'properties' => []
                                    ],
                                    'Message' => ['type' => 'keyword', 'index' => false, 'doc_values' => false], // 禁止索引，聚合，排序，脚本操作
                                    '@timestamp' => [
                                        'type' => 'date',
                                        'format' => 'yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_second'
                                    ]
                                ],
                                'dynamic_templates' => [
                                    [
                                        'olog' => [
                                            'path_match' => 'Option.*',
                                            'mapping' => [
                                                'type' => 'keyword',
                                                'ignore_above' => 256,
                                                'norms' => false
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]);
            }
            $this->initialize = true;
        } catch (\Exception $e) {
        }
    }

    public function write($level, $logid, array $option ,$message, int $time)
    {
        $this->logs[] = [
            'Level' => $level,
            'Logid' => $logid,
            'Option' => $option,
            'Message' => $message,
            '@timestamp' => $time
        ];
    }

    public function realWrite()
    {
        $this->initialize();
        if (!$this->initialize) {
            $this->logs = [];
            return;
        }
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
        try {
            $this->escli->bulk($data);
        } catch (\Exception $e) {

        }
    }
}