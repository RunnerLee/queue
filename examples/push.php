<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */
require __DIR__.'/../vendor/autoload.php';

require __DIR__.'/Jobs/Demo.php';

$factory = new \Runner\Queue\QueueFactory([
    'redis' => [
        'host'     => '127.0.0.1',
        'port'     => '6379',
        'auth'     => null,
        'database' => 5,
    ],
]);

$queue = $factory->connection('redis');

$times = 0;

while (true) {

    $random = random_int(20, 30);

    for ($i = 0; $i < $random; ++$i) {
        $queue->push(
            json_encode([
                'max_retries' => 5,
                'timeout'     => 5,
                'attempts'    => 0,
                'job'         => serialize(new Demo()),
            ]),
            'default'
        );

        usleep(random_int(100, 1000));
    }

    $times += $random;

    echo "{$times}\n";

    sleep(2);
}