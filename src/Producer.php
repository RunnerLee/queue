<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */

namespace Runner\Queue;

use FastD\Swoole\Process;
use Runner\Queue\Contracts\QueueInterface;
use Runner\Queue\Utils\Detectable;
use swoole_process;

class Producer extends Process
{
    use Detectable;

    /**
     * @var QueueInterface
     */
    protected $connection;

    /**
     * @var string
     */
    protected $queue;

    /**
     * @var int
     */
    protected $sleep;

    /**
     * @var int
     */
    protected $consumerNum;

    /**
     * @param string $queue
     *
     * @return $this
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * @param QueueInterface $connection
     *
     * @return $this
     */
    public function setConnection(QueueInterface $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @param int $number
     *
     * @return $this
     */
    public function setConsumerNum($number)
    {
        $this->consumerNum = $number;

        return $this;
    }

    /**
     * @param int $seconds
     *
     * @return $this
     */
    public function setSleep($seconds)
    {
        $this->sleep = $seconds;

        return $this;
    }

    /**
     * @param swoole_process $worker
     *
     * @return void
     */
    public function handle(swoole_process $worker)
    {
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
             * 当等待被消费者提取的任务大于消费者总数的时候
             * 死循环检查等待提取的任务是否有减少, 没减少则一次循环休眠半秒
             * 避免任务一直被提取, 但是却没被执行
             */
            if (($waitingJobs = $worker->statQueue()['queue_num']) > $this->consumerNum) {
                while (true) {
                    usleep(500000);
                    if ($waitingJobs != $worker->statQueue()['queue_num']) {
                        break;
                    }
                }
            }
        }
    }
}
