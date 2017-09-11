<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */

require __DIR__ . '/../vendor/autoload.php';

$queue = new \Runner\Queue\Queues\RedisQueue([
    'host' => '127.0.0.1',
    'port' => '6379',
    'auth' => null,
    'database' => 5,
]);

$queue->push(
    json_encode([
        'max_retries' => 5,
        'timeout' => 10,
        'attempts' => 0,
//        'job' => serialize(new \Runner\Queue\Demo\Alpha()),
        'job' => serialize(new \Runner\Queue\Demo\Beta()),
    ]),
    'default'
);