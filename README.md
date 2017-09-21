# Queue

基于 Swoole Process 实现多消费者任务队列. 功能:

* 多驱动支持
* 消费者进程异常退出自动重启
* 任务超时自动中止
* 任务异常自动重试

嗯.. 没错又是抄的, laravel 的队列.

### 驱动要求

### 使用


```php
<?php

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
        'name'         => 'runner',     // 任务名称
        'listen'       => 'default',    // 监听队列
        'pid_path'     => __DIR__,      // pid 文件目录
        'consumer_num' => 3,            // 消费者数量
        'queue_key'    => 1000000,      // 消息队列 key
        'retry_after'  => 60,           // 失败 / 超时 重试时间
        'sleep'        => 2,            // 睡眠时间
    ]
);

$schedule->setQueue($queueFactory->connection('redis'));

$schedule->on('consumerReboot', function () {
    echo "consumer reboot\n";
});

$schedule->on('start', function () {
    echo "mission started\n";
});

$schedule->on('shutdown', function () {
    echo "mission stop\n";
});

$schedule->start();
```

