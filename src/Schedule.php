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

    /**
     * @var array
     */
    protected $eventListeners = [];

    /**
     * @var bool
     */
    protected $shutdown = false;

    /**
     * Schedule constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        parent::__construct("{$this->config['name']} queue schedule");
    }

    /**
     * @param QueueInterface $queue
     *
     * @return $this
     */
    public function setQueue(QueueInterface $queue)
    {
        $queue->setRetryAfter($this->config['retry_after']);
        $this->queue = $queue;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function start()
    {
        if (process_is_running($this->name)) {
            throw new Exception("queue {$this->config['name']} is running");
        }

        /*
         * 用于当关闭队列时, 向消息队列推送关闭指令给消费者
         */
        $this->process->useQueue($this->config['queue_key']);

        /**
         * 启动调度器进程.
         */
        $pid = $this->process->start();

        $this->fireEvent('start');

        return $pid;
    }

    /**
     * @return void
     */
    public function shutdown()
    {
        if ($this->started()) {
            swoole_process::kill(file_get_contents($this->getPidFile()), SIGTERM);
        }
    }

    /**
     * @return bool
     */
    public function started()
    {
        if (file_exists($this->getPidFile()) && $pid = file_get_contents($this->getPidFile())) {
            return swoole_process::kill($pid, 0);
        }
        return false;
    }

    /**
     * @return void
     */
    public function handle(swoole_process $worker)
    {
        /*
         * 创建消费者
         */
        $this->makeConsumers();

        /*
         * 创建生产者
         */
        $this->makeProducer();

        /*
         * 保存 pid 文件
         */
        $this->savePidToFile();

        /*
         * 注册回收子进程监听
         */
        $this->registerWaitChildProcessHandler();

        /*
         * 注册进程关闭监听
         */
        $this->registerShutdownHandler();
    }

    /**
     * @param $event
     * @param $callback
     *
     * @return $this
     */
    public function on($event, $callback)
    {
        $this->eventListeners[$event] = $callback;

        return $this;
    }

    /**
     * @return void
     */
    protected function makeConsumers()
    {
        for ($i = 0; $i < $this->config['consumer_num']; $i++) {
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

    /**
     * @return void
     */
    protected function makeProducer()
    {
        $this->producer = new Producer("{$this->config['name']} queue producer");

        $this
            ->producer
            ->setQueue($this->config['listen'])
            ->setConnection($this->queue)
            ->setSleep($this->config['sleep'])
            ->setConsumerNum($this->config['consumer_num'])
            ->getProcess()->useQueue($this->config['queue_key']);
        $this->producer->start();

        $this->fireEvent('producerStart');
    }

    /**
     * @return void
     */
    protected function registerWaitChildProcessHandler()
    {
        swoole_process::signal(SIGCHLD, function () {
            while ($ret = swoole_process::wait(false)) {
                foreach ($this->consumers as $key => $consumer) {
                    if ($ret['pid'] === $consumer->getProcess()->pid) {
                        if ($this->shutdown) {
                            unset($this->consumers[$key]);
                            $this->fireEvent('consumerShutdown');
                        } else {
                            $consumer->start();
                            $this->fireEvent('consumerReboot');
                        }
                    }
                }
                if ($ret['pid'] === $this->producer->getProcess()->pid) {
                    $this->fireEvent('producerShutdown');
                }
            }
        });
    }

    /**
     * @return void
     */
    protected function registerShutdownHandler()
    {
        swoole_process::signal(SIGTERM, function () {
            swoole_process::kill($this->producer->process->pid, SIGTERM);
            $this->shutdown = true;
            $num = 0;
            foreach ($this->consumers as $consumer) {
                $consumer->started() && $num++;
            }

            for ($i = 0; $i < $num; $i++) {
                $this->process->push('queue_shutdown');
            }
            $this->fireEvent('shutdown');
            exit(0);
        });
    }

    /**
     * @return void
     */
    protected function savePidToFile()
    {
        file_put_contents(
            $this->getPidFile(),
            $this->process->pid
        );
    }

    /**
     * @return string
     */
    protected function getPidFile()
    {
        return "{$this->config['pid_path']}/{$this->config['name']}_queue.pid";
    }

    /**
     * @return void
     */
    protected function fireEvent($event)
    {
        if (array_key_exists($event, $this->eventListeners)) {
            try {
                call_user_func($this->eventListeners[$event]);
            } catch (Exception $e) {
            } catch (Throwable $e) {
            }
        }
    }
}
