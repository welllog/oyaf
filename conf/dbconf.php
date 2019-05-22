<?php
/**
 * Created by PhpStorm.
 * User: chentairen
 * Date: 2019/2/19
 * Time: 下午7:14
 */
return [
    'dev' => [
        'db' => [
            'default' => [     // 默认配置
                'driver' => 'mysql',   // pgsql(postgresql)
                'host' => '127.0.0.1',
                'port' => '3306',
                'username' => 'root',
                'password' => '',
                'dbname' => '',
                'charset' => 'utf8',
                'pconnect' => false,
                'time_out' => 3,
                'prefix' => '',
                'throw_exception' => true
            ]
        ],
        'redis' => [
            'default' => [
                'host' => '127.0.0.1',
                'port' => '6379',
                'password' => '',
                'time_out' => 3,
                'database' => 0,
            ],
            'cache' => [
                'host' => '127.0.0.1',
                'port' => '6379',
                'password' => '',
                'time_out' => 3,
                'database' => 5,
            ]
        ],
        'cdb' => [
            'default' => [
                'host' => '127.0.0.1',
                'port' => 9045,
                'keyspace' => 'base',
                'username' => '',
                'password' => '',
                'heart_beat_intval' => 20,  // 心跳
                'consistency' => 'one',  // 一致性级别
                'connect_timeout' => 0.5, // 500ms超时
                'request_timeout' => 0.5, // 500ms请求超时
                'max_links' => 80,
                'min_links' => 10,
            ]
        ],
        'es' => [
            'host' => '127.0.0.1',
            'port' => 9200
        ]
    ],
    'product' => [
        'db' => [
            'default' => [     // 默认配置
                'driver' => 'mysql',   // pgsql(postgresql)
                'host' => '127.0.0.1',
                'port' => '3306',
                'username' => 'root',
                'password' => '',
                'dbname' => '',
                'charset' => 'utf8',
                'pconnect' => false,
                'time_out' => 3,
                'prefix' => '',
                'throw_exception' => true
            ]
        ],
        'redis' => [
            'default' => [
                'host' => '127.0.0.1',
                'port' => '6379',
                'password' => '',
                'time_out' => 3,
                'database' => 0,
            ],
            'cache' => [
                'host' => '127.0.0.1',
                'port' => '6379',
                'password' => '',
                'time_out' => 3,
                'database' => 5,
            ]
        ],
        'cdb' => [
            'default' => [
                'host' => '127.0.0.1',
                'port' => 9045,
                'keyspace' => 'base',
                'username' => '',
                'password' => '',
                'heart_beat_intval' => 20,  // 心跳
                'consistency' => 'one',  // 一致性级别
                'connect_timeout' => 0.5, // 500ms超时
                'request_timeout' => 0.5, // 500ms请求超时
                'max_links' => 80,
                'min_links' => 10,
            ]
        ],
        'es' => [
            'host' => '127.0.0.1',
            'port' => 9200
        ]
    ]
];