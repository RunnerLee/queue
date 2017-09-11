<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */

namespace Runner\Queue;

use FastD\Swoole\Process;
use Runner\Queue\Queues\RedisQueue;
use swoole_process;

class Schedule extends Process
{

    protected $config;

    protected $consumers;

    protected $producer;

    protected $queue;

    public function __construct(array $config)
    {
        $this->config = $config;
        parent::__construct("{$this->config['name']} queue schedule");
    }

    public function start()
    {
        $this->makeQueue();

        $pid = parent::start();

        file_put_contents($this->config['pid_file'], $pid);

        return $pid;
    }

    public function handle(swoole_process $worker)
    {
        swoole_set_process_name($this->name);
        $this->makeConsumers();
        $this->makeProducer();
        $this->registerConsumerAutoRebootHandler();
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
            ->setConnection($this->queue)
            ->setSleep($this->config['sleep']);

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
                $this->queue = new RedisQueue($this->config['connections']);
                break;
        }
    }
}
