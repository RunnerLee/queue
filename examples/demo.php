<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */
require __DIR__ . '/../vendor/autoload.php';

$schedule = new \Runner\Queue\SwooleSchedule([
    'name' => 'fucker',
    'listen' => 'default',
    'pid_file' => __DIR__ . '/queue_schedule.pid',
    'consumer_num' => 5,
    'queue_key' => random_int(1000000, 9999999),
    'driver' => 'redis',
    'sleep' => 2,
    'connections' => [
        'host' => '127.0.0.1',
        'port' => '6379',
        'auth' => null,
        'database' => 5,
    ],
]);

$schedule->start();