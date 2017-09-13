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
use swoole_process;
use Exception;

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

        $this->process->useQueue($this->config['queue_key']);

        $this->daemon();

        return $this->process->start();
    }

    public function shutdown()
    {
        if (process_is_running($this->name)) {

            list($schedule, $producer) = explode(',', file_get_contents($this->config['pid_file']));

            swoole_process::kill($producer, SIGTERM);
        }
    }

    public function handle(swoole_process $worker)
    {
        process_rename($this->name);
        $this->makeConsumers();
        $this->makeProducer();

        $this->savePidsToFile();

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
                    if ($ret['pid'] === $consumer->getProcess()->pid && $ret['signal'] === SIGTERM) {
                        $pid = $consumer->start();
                        echo "consumer restarted: {$pid}\n";
                        break;
                    }
                }
                if ($ret['pid'] === $this->producer->getProcess()->pid) {
                    echo "\n";
                    echo "producer {$this->producer->process->pid} exit\n";
                    for ($i = 0; $i < $this->config['consumer_num']; ++$i) {
                        $this->process->push('queue_shutdown');
                    }

                    echo "schedule {$this->process->pid} exit\n";

                    exit(0);
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

    protected function savePidsToFile()
    {
        $pids = "{$this->process->pid},{$this->producer->getProcess()->pid}";

        file_put_contents($this->config['pid_file'], $pids);
    }
}
