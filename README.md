# Queue

基于 Swoole Process 实现多消费者任务队列. 功能:

* 消费者进程异常退出自动重启
* 任务超时自动中止
* 任务异常自动重试

嗯.. 没错又是抄的, laravel 的队列.