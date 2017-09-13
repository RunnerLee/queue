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
use Throwable;

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

    protected $eventListeners = [];

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

        $pid = $this->process->start();

        $this->fireEvent('start');

        return $pid;
    }

    public function shutdown()
    {
        if (process_is_running($this->name)) {
            list($schedule, $producer) = explode(
                ',',
                file_get_contents("{$this->config['pid_path']}/{$this->config['name']}_queue.pid")
            );
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

    public function on($event, $callback)
    {
        $this->eventListeners[$event] = $callback;

        return $this;
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

        $this->fireEvent('consumerStart');
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

        $this->fireEvent('producerStart');
    }

    protected function registerConsumerAutoRebootHandler()
    {
        while (true) {
            if ($ret = swoole_process::wait()) {
                foreach ($this->consumers as $consumer) {
                    if ($ret['pid'] === $consumer->getProcess()->pid) {
                        if ($ret['signal'] === SIGTERM) {
                            $consumer->start();
                            $this->fireEvent('consumerReboot');
                        } else {
                            $this->fireEvent('consumerShutdown');
                        }
                    }
                }
                if ($ret['pid'] === $this->producer->getProcess()->pid) {
                    $this->fireEvent('producerShutdown');
                    for ($i = 0; $i < $this->config['consumer_num']; ++$i) {
                        $this->process->push('queue_shutdown');
                    }
                    $this->fireEvent('shutdown');
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

        file_put_contents($this->config['pid_path']."/{$this->config['name']}_queue.pid", $pids);
    }

    protected function fireEvent($event)
    {
        if (!array_key_exists($event, $this->eventListeners)) {
            return 0;
        }

        try {
            call_user_func($this->eventListeners[$event]);
        } catch (Exception $e) {
        } catch (Throwable $e) {
        }
    }
}
