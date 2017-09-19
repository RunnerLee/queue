<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */

namespace Runner\Queue;

use FastD\Swoole\Process;
use Runner\Queue\Contracts\QueueInterface;
use swoole_process;

class Producer extends Process
{
    /**
     * @var QueueInterface
     */
    protected $connection;

    /**
     * @var string
     */
    protected $queue;

    /**
     * @var integer
     */
    protected $sleep;

    /**
     * @var integer
     */
    protected $consumerNum;

    /**
     * @param string $queue
     * @return $this
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * @param QueueInterface $connection
     * @return $this
     */
    public function setConnection(QueueInterface $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @param integer $number
     * @return $this
     */
    public function setConsumerNum($number)
    {
        $this->consumerNum = $number;

        return $this;
    }

    /**
     * @param integer $seconds
     * @return $this
     */
    public function setSleep($seconds)
    {
        $this->sleep = $seconds;

        return $this;
    }

    /**
     * @return void
     */
    public function start()
    {
        if (true === $this->daemonize) {
            $this->process->daemon();
        }

        return $this->process->start();
    }

    /**
     * @param swoole_process $worker
     * @return void
     */
    public function handle(swoole_process $worker)
    {
        process_rename($this->name);
        while (true) {
            list($payload, $reserved) = $this->connection->pop($this->queue);

            if (is_null($payload)) {
                sleep($this->sleep);
                continue;
            }

            $worker->push(json_encode([
                $payload, $reserved,
            ]));

            /**
             * 如果消息队列中的数量大于消费者总数, 先休眠一小会
             */
            if ($worker->statQueue()['queue_num'] > $this->consumerNum) {
                sleep($this->sleep);
            }
        }
    }
}
