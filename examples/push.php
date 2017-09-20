<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */
require __DIR__.'/../vendor/autoload.php';

require __DIR__.'/Jobs/Alpha.php';
require __DIR__.'/Jobs/Beta.php';

$factory = new \Runner\Queue\QueueFactory([
    'redis' => [
        'host'     => '127.0.0.1',
        'port'     => '6379',
        'auth'     => null,
        'database' => 5,
    ],
]);

$queue = $factory->connection('redis');

//$queue->push(
//    json_encode([
//        'max_retries' => 5,
//        'timeout'     => 5,
//        'attempts'    => 0,
//        'job'         => serialize(new Beta()),
//    ]),
//    'default'
//);

$job = new Alpha();

while (true) {
    $number = random_int(50, 99);
    echo "going to push {$number} jobs to the queue\n";
    for ($i = 0; $i < $number; ++$i) {
        $queue->push(
            json_encode([
                'max_retries' => 5,
                'timeout'     => 2,
                'attempts'    => 0,
                'job'         => serialize($job),
            ]),
            'default'
        );
    }
    echo "sleeping...\n";
    sleep(2);
}

//$queue->push(
//    json_encode([
//        'max_retries' => 5,
//        'timeout'     => 10,
//        'attempts'    => 0,
//        'job'         => serialize(new Beta()),
//    ]),
//    'default'
//);
