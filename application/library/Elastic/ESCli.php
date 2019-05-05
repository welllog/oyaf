<?php
/**
 * Created by PhpStorm.
 * User: chentairen
 * Date: 2019/5/1
 * Time: 上午1:33
 */

namespace Elastic;

use Elasticsearch\ClientBuilder;
use Enum\RgtEnum;
use Yaf\Registry;

class ESCli
{
    private static $_instance;
    /** @var \Elasticsearch\Client  */
    private $_escli;
    public $error;
    public $code;
    public $result;

    public $clientParams = [
        'ignore' => 404,
//            'verbose' => true,  // details
        'connect_timeout' => 1,
        'timeout' => 1,
//            'future' => 'lazy', // future mode,适合处理批量处理请求，一些请求会返回原始响应($client->exsist)
//            'verify' => 'path/to/cacert.pem' //ssl
    ];

    private function __construct()
    {
        $conf = Registry::get(RgtEnum::DB_CONF)['es'];
        if (!$conf) {
            throw new \Exception("es 配置异常");
        }
        $hosts[] = $conf['host'] . ':' . $conf['port'];
        $this->_escli = ClientBuilder::create()
            ->setHosts($hosts)
            //'\Elasticsearch\ConnectionPool\StaticNoPingConnectionPool' 默认静态连接池
//            ->setConnectionPool(SniffingConnectionPool::class, [])  // 动态链接池，会自动发现集群节点
            //默认重试次数为节点数量
//            ->setRetries(3)
//            ->setSSLVerification('path/to/cacert.pem') // ssl
            ->build();
    }

    /**
     * @return ESCli
     * @throws \Exception
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * @return \Elasticsearch\Client
     */
    public function getClient()
    {
        return $this->_escli;
    }

    /**
     * @return array
     */
    public function indices()
    {
        return $this->_escli->cat()->indices();
    }

    /**
     * 返回索引文档数量，索引不存在返回-1
     * @param string $index
     * @param bool $async
     * @return int|AsyncESResponse
     */
    public function docCount(string $index, $async = false)
    {
        $client = $this->clientParams;
        if ($async) $client['future'] = 'lazy';
        $res = $this->_escli->cat()->count(['index' => $index, 'client' => $client]);
        if ($async) {
            return new AsyncESResponse($res, function($p) {
                return isset($p['error']) ? -1 : $p[0]['count'];
            });
        }
        return isset($res['error']) ? -1 : $res[0]['count'];
    }

    /**
     * 删除索引
     * @param $index
     * @param bool $async
     * @return bool|AsyncESResponse
     */
    public function delIndex($index, $async = false)
    {
        $client = $this->clientParams;
        if ($async) $client['future'] = 'lazy';
        $index = is_array($index) ? $index : [$index];
        $res = $this->_escli->indices()->delete(['index' => $index, 'client' => $client]);
        if ($async) {
            return new AsyncESResponse($res, function($p) {
                return isset($p['error']) ? false : true;
            });
        }
        return isset($res['error']) ? false : true;
    }

    // 创建索引
    public function createIndex(string $index, array $body)
    {
        $this->_escli->indices()->create([
            'index' => $index,
            'body' => $body
        ]);
    }

    /**
     * 获取索引配置
     * @param string[] ...$index 可传多个索引
     * @return array|null
     */
    public function getSettings(string ...$index) : ?array
    {
        $res = $this->_escli->indices()->getSettings(['index' => $index, 'client' => $this->clientParams]);
        return isset($res['error']) ? null : $res;
    }

    /**
     * 更改索引配置
     * @param string $index
     * @param array $setting
     * @return bool
     */
    public function putSetting(string $index, array $setting) : bool
    {
        $res = $this->_escli->indices()->putSettings(['index' => $index, 'body' => [
            'settings' => $setting
        ], 'client' => $this->clientParams]);
        return isset($res['error']) ? false : true;
    }

    // 获取mapping
    public function getMapping(string ...$index) : ?array
    {
        // ['index' => xx, 'type' => xx]   ['index' => xx] ['index' => [xx, xx]]
        $res = $this->_escli->indices()->getMapping(['index' => $index, 'client' => $this->clientParams]);
        return isset($res['error']) ? null : $res;
    }

    /**
     * 修改mapping
     * @param string $index
     * @param array $mapping
     * @return bool
     */
    public function putMapping(string $index, array $mapping) : bool
    {
        $res = $this->_escli->indices()->putMapping(['index' => $index, 'type' => $index, 'body' => [
            $index => $mapping
        ], 'client' => $this->clientParams]);
        return isset($res['error']) ? false : true;
    }

    /**
     * 获取索引信息
     * @param string[] ...$index
     * @return array|null
     */
    public function statsIndex(string ...$index) : ?array
    {
        $res = $this->_escli->indices()->stats(['index' => $index, 'client' => $this->clientParams]);
        return isset($res['error']) ? null : $res;
    }

    // 获取别名，空为所有索引的别名
    public function getAlias(string ...$index) : ?array
    {
        $res = $this->_escli->indices()->getAliases(['index' => $index, 'client' => $this->clientParams]);
        return isset($res['error']) ? null : $res;
    }

    /**
     * 修改别名
     * @param array $put
     * @return bool
     */
    public function putAlias(array $put) : bool
    {
        // ['add' => ['index' => xx, 'alias' => yy]]   remove
        $res = $this->_escli->indices()->updateAliases([
            'body' => [
                'actions' => $put
            ],
            'client' => $this->clientParams
        ]);
        return isset($res['error']) ? false : true;
    }

    /**
     * @param $index
     * @param $id
     * @param bool $async
     * @return AsyncESResponse|null
     */
    public function find($index, $id, $async = false)
    {
        $client = $this->clientParams;
        if ($async) $client['future'] = 'lazy';
        $params = [
            'index' => $index,
            'type'  => $index,
            'id' => $id,
            'client' => $client
        ];
        $res = $this->_escli->get($params);
        if ($async) {
            return new AsyncESResponse($res, function($p) {
                if (!empty($p['found'])) {
                    return $p['_source'];
                }
                return null;
            });
        }
        if (!empty($res['found'])) {
            return $res['_source'];
        }
        return null;
    }

    /**
     * 索引单条数据
     * @param $index
     * @param $primaryKey
     * @param $data
     * @return array|void
     */
    public function index($index, $primaryKey, $data)
    {
        if (empty($data)) return;
        $primaryKey = is_array($primaryKey) ? $primaryKey : [$primaryKey];
        asort($primaryKey);
        $_id = '';
        foreach ($primaryKey as $pri) {
            $_id .= ':' . $data[$pri];
        }
        $_id = ltrim($_id, ':');
        $params = [
            'index' => $index,
            'type' => $index,
            'id' => $_id,
            'body' => $data,
            'client' => $this->clientParams
        ];
        return $this->_escli->index($params);
    }

    /**
     * 索引多条数据
     * @param $index
     * @param $primaryKey
     * @param $data
     * @return array|void
     */
    public function indexMultiple($index, $primaryKey, $data)
    {
        if (empty($data)) return;
        $i = 0;
        $primaryKey = is_array($primaryKey) ? $primaryKey : [$primaryKey];
        foreach ($data as $row) {
            ++$i;
            $_id = '';
            if (count($primaryKey) > 1) {
                asort($primaryKey);
                foreach ($primaryKey as $pri) {
                    $_id .= ':' . $row[$pri];
                }
                $_id = ltrim($_id, ':');
            } else {
                $_id = $row[$primaryKey[0]];
            }

            $params['body'][] = [
                'index' => [
                    '_index' => $index,
                    '_type' => $index,
                    '_id' => $_id
                ]
            ];
            $params['body'][] = $row;
            if ($i % 1000 == 0) {
                $responses = $this->_escli->bulk($params);

                // erase the old bulk request
                $params = ['body' => []];

                // unset the bulk response when you are done to save memory
                unset($responses);
            }
        }
        if (!empty($params['body'])) {
            $responses = $this->_escli->bulk($params);
        }
        return $responses;
    }

    /**
     * 更新索引
     * @param $index
     * @param $primaryKeys
     * @param $data
     * @param bool $async
     * @return bool|AsyncESResponse
     */
    public function update($index, $primaryKeys, $data, $async = false)
    {
        $primaryKeys = is_array($primaryKeys) ? $primaryKeys : [$primaryKeys];
        asort($primaryKeys);
        $id = '';
        foreach ($primaryKeys as $pri) {
            $id .= ':' . $data[$pri];
        }
        $id = ltrim($id, ':');
        $client = $this->clientParams;
        if ($async) $client['future'] = 'lazy';
        $params = [
            'index' => $index,
            'type' => $index,
            'id' => $id,
            'body' => [
                'doc' => $data
            ],
            'client' => $client
        ];
        $res = $this->_escli->update($params);
        if ($async) {
            return new AsyncESResponse($res, function($p) {
                return isset($p['error']) ? false : true;
            });
        }
        return isset($res['error']) ? false : true;
    }

    /**
     * 删除索引
     * @param $index
     * @param $primaryKeys
     * @param $data
     * @param bool $async
     * @return bool|AsyncESResponse
     */
    public function delete($index, $primaryKeys, $data, $async = false)
    {
        $primaryKeys = is_array($primaryKeys) ? $primaryKeys : [$primaryKeys];
        asort($primaryKeys);
        $id = '';
        foreach ($primaryKeys as $pri) {
            $id .= ':' . $data[$pri];
        }
        $id = ltrim($id, ':');
        $client = $this->clientParams;
        if ($async) $client['future'] = 'lazy';
        $params = [
            'index' => $index,
            'type' => $index,
            'id' => $id,
            'client' => $client
        ];
        $res = $this->_escli->delete($params);
        if ($async) {
            return new AsyncESResponse($res, function($p) {
                return isset($p['error']) ? false : true;
            });
        }
        return isset($res['error']) ? false : true;
    }

    /**
     * reindex
     * @param $srcIndex
     * @param $desIndex
     * @return bool
     */
    public function reindex($srcIndex, $desIndex)
    {
        $params = [
            'source' => ['index' => $srcIndex],
            'dest' => ['index' => $desIndex]
        ];
        $res = $this->_escli->reindex($params);
        return isset($res['error']) ? false : true;
    }

    /**
     * @param $params
     * @param bool $async
     * @return array|AsyncESResponse
     */
    public function search($params, $async = false)
    {
        $params['client'] = $this->clientParams;
        if ($async) $params['client']['future'] = 'lazy';
        $res = $this->_escli->search($params);
        if ($async) {
            return new AsyncESResponse($res, function($p) {
                if (isset($p['error'])) return ['total' => 0, 'hits' => []];
                $list = [];
                foreach ($p['hits']['hits'] as $row) {
                    $list[] = $row['_source'];
                }
                $res = $p['hits'];
                $res['hits'] = $list;
                return $res;
            });
        }
        if (isset($res['error'])) return ['total' => 0, 'hits' => []];
        $list = [];
        foreach ($res['hits']['hits'] as $row) {
            $list[] = $row['_source'];
        }
        $res = $res['hits'];
        $res['hits'] = $list;
        return $res;
    }

    public function delByQuery($params, $async = false)
    {
        $params['client'] = $this->clientParams;
        if ($async) $params['client']['future'] = 'lazy';
        $res = $this->_escli->deleteByQuery($params);
        if ($async) {
            return new AsyncESResponse($res, function($p) {
                return isset($p['error']) ? false : true;
            });
        }
        return isset($res['error']) ? false : true;
    }

    public function updateByQuery($params, $async = false)
    {
        $params['client'] = $this->clientParams;
        if ($async) $params['client']['future'] = 'lazy';
        $res = $this->_escli->updateByQuery($params);
        if ($async) {
            return new AsyncESResponse($res, function($p) {
                return isset($p['error']) ? false : true;
            });
        }
        return isset($res['error']) ? false : true;
    }
}