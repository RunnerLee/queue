<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */
$process = new swoole_process(function (swoole_process $worker) {
    swoole_set_process_name('motherfucker');

    swoole_process::signal(SIGALRM, function () {
        echo '123';
    });
//    swoole_process::signal(SIGUSR2, function () {
//        echo "goodbye\n";
//        exit;
//    });
    swoole_process::alarm(100000000);
    swoole_process::signal(SIGALRM, null);
    swoole_process::alarm(-1);
    echo "I'm shit\n";
    exit;
});

echo $process->start()."\n";
