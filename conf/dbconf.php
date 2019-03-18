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
                'password' => '123',
                'dbname' => 'dc',
                'charset' => 'utf8',
                'pconnect' => false,
                'time_out' => 3,
                'prefix' => 'test_',
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
        ]
    ],
    'product' => [
        'db' => [
            'default' => [     // 默认配置
                'driver' => 'mysql',   // pgsql(postgresql)
                'host' => '127.0.0.1',
                'port' => '3306',
                'username' => 'root',
                'password' => 'iM8fHw3N5Pq2wDrr',
                'dbname' => 'dc',
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
        ]
    ]
];