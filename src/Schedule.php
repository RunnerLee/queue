<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */

namespace Runner\Queue;

use Exception;
use FastD\Swoole\Process;
use Runner\Queue\Contracts\QueueInterface;
use Runner\Queue\Queues\RedisQueue;
use swoole_process;

class Schedule extends Process
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var Consumer[]
     */
    protected $consumers;

    /**
     * @var Producer
     */
    protected $producer;

    /**
     * @var QueueInterface
     */
    protected $queue;

    public function __construct(array $config)
    {
        $this->config = $config;
        parent::__construct("{$this->config['name']} queue schedule");
    }

    public function start()
    {
        if (process_is_running($this->name)) {
            throw new Exception("queue {$this->config['name']} is running");
        }

        $this->makeQueue();

        $pid = parent::start();

        file_put_contents($this->config['pid_file'], $pid);

        $this->daemon();

        return $pid;
    }

    public function shutdown()
    {
        if (process_is_running($this->name)) {
            posix_kill(file_get_contents($this->config['pid_file']), SIGTERM);
        }
    }

    public function handle(swoole_process $worker)
    {
        process_rename($this->name);
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
                $this->queue = new RedisQueue($this->config['connections'], $this->config['retry_after']);
                break;
        }
    }
}
