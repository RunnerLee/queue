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

use \Runner\Queue\Schedule;

$config = [
    'name'         => 'fucker',     // 任务名称
    'listen'       => 'default',    // 监听队列
    'pid_path'     => __DIR__,      // pid 文件路径
    'consumer_num' => 3,            // 消费者数量
    'queue_key'    => random_int(1000000, 9999999), // swoole queue key
    'retry_after'  => 60,   // 失败重试时间
    'driver'       => 'redis',  // 驱动
    'sleep'        => 2,    // 无任务时睡眠时间
    'connections'  => [ // 驱动连接配置
        'host'     => '127.0.0.1',
        'port'     => '6379',
        'auth'     => null,
        'database' => 5,
    ],
];

$schedule = new Schedule($config);

$schedule->start();
```

