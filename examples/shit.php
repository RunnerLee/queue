<?php
/**
 * @auth: RunnerLee
 * @email: runnerleer@gmail.com
 * @website: https://runnerlee.com
 * @time: 2017-09
 */

$process = new swoole_process(function (swoole_process $worker) {
    $result = swoole_process::signal(SIGUSR1, function () {
        file_put_contents(__DIR__ . '/shit.log', 123);
    });
    while (true) {
        $worker->pop();
    }
});

swoole_set_process_name('motherfucker');

$process->useQueue();

$pid = $process->start();

echo "pid: {$pid}\n\n";


//swoole_process::kill($pid, SIGUSR1);


