<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */
require __DIR__.'/../vendor/autoload.php';

require __DIR__.'/Jobs/Alpha.php';
require __DIR__.'/Jobs/Beta.php';

$queueFactory = new \Runner\Queue\QueueFactory([
    'redis' => [
        'host'     => '127.0.0.1',
        'port'     => '6379',
        'auth'     => null,
        'database' => 5,
    ],
]);

$schedule = new \Runner\Queue\Schedule(
    [
        'name'         => 'runner',
        'listen'       => 'default',
        'pid_path'     => __DIR__,
        'consumer_num' => 3,
        'queue_key'    => 1000000,
        'retry_after'  => 60,
        'sleep'        => 2,
    ],
    $queueFactory->connection('redis')
);

$schedule->on('consumerReboot', function () {
    echo "consumer reboot\n";
});

$schedule->start();
//$schedule->shutdown();
