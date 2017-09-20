<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */
require __DIR__.'/../vendor/autoload.php';

require __DIR__ . '/Jobs/Alpha.php';
require __DIR__ . '/Jobs/Beta.php';

$schedule = new \Runner\Queue\Schedule([
    'name'         => 'runner',
    'listen'       => 'default',
    'pid_path'     => __DIR__,
    'consumer_num' => 3,
    'queue_key'    => random_int(1000000, 9999999),
    'retry_after'  => 60,
    'driver'       => 'redis',
    'sleep'        => 2,
    'connection'  => [
        'host'     => '127.0.0.1',
        'port'     => '6379',
        'auth'     => null,
        'database' => 5,
    ],
]);

$schedule->start();
//$schedule->shutdown();
