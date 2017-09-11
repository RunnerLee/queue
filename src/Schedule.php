<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */

namespace Runner\Queue;

use FastD\Swoole\Process;
use Runner\Queue\Contracts\QueueInterface;
use Runner\Queue\Queues\RedisQueue;
use Exception;
use swoole_process;

class Schedule
{

    protected $config = [];

    /**
     * @var Consumer[]
     */
    protected $consumers = [];

    /**
     * @var Producer
     */
    protected $producer;

    /**
     * @var Process
     */
    protected $worker;

    /**
     * @var QueueInterface
     */
    protected $queue;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function run()
    {
        if (process_is_running("{$this->config['name']} queue schedule")) {
            throw new Exception('queue is running');
        }
        $this->bootstrap();

        if (!$pid = $this->worker->start()) {
            throw new Exception('start queue schedule failed');
        }
        file_put_contents($this->config['pid_file'], $pid);
    }

    public function shutdown()
    {
        if (!process_is_running("{$this->config['name']} queue schedule")) {
            return false;
        }
        swoole_process::kill(file_get_contents($this->config['pid_file']), SIGTERM);
    }

    public function listen()
    {
        return $this->config['listen'];
    }

    public function getSwooleQueueKey()
    {
        return $this->config['queue_key'];
    }

    public function queue()
    {
        return $this->queue;
    }

    protected function bootstrap()
    {
        $this->worker = new Process(
            "{$this->config['name']} queue schedule",
            function (swoole_process $worker) {
                process_rename("{$this->config['name']} queue schedule");

                /**
                 * 创建消费者, 消费者内各自监听队列
                 */
                $this->makeConsumers();

                $this->makeProducer();
                /**
                 * 创建生产者, 生产者自动
                 */

                /**
                 * 注册进程回收
                 */
                $this->registerConsumerAutoRebootHandler();
            }
        );

        /**
         * 默认守护进程
         */
        $this->worker->daemon();

        /**
         * 创建队列
         */
        $this->queue = $this->makeQueue();
    }

    protected function makeConsumers()
    {
        for ($i = 0; $i < $this->config['consumer_num']; ++$i) {
            $consumer = new Consumer("{$this->config['name']} queue consumer");
            $consumer
                ->setQueue($this->config['listen'])
                ->setConnection($this->queue);
            $consumer->getProcess()->useQueue($this->config['queue_key']);
            $consumer->start();
            $this->consumers[] = $consumer;
        }
    }

    protected function makeProducer()
    {
        $this->producer = new Producer("{$this->config['name']} queue producer");

        $this
            ->producer
            ->setQueue($this->config['listen'])
            ->setConnection($this->queue);

        $this->producer->getProcess()->useQueue($this->config['queue_key']);

        $this->producer->start();
    }

    protected function registerConsumerAutoRebootHandler()
    {
        while (true) {
            if ($ret = swoole_process::wait()) {
                foreach ($this->consumers as $consumer) {
                    if ($ret['pid'] === $consumer->pid()) {
                        $pid = $consumer->start();
                        echo "consumer restarted: {$pid}\n";
                        break;
                    }
                }
            }
        }
    }

    protected function makeQueue()
    {
        switch ($this->config['driver']) {
            case 'redis':
                return new RedisQueue($this->config['connections']);
        }
    }
}
