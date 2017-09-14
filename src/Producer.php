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

    protected $queue;

    protected $sleep;

    public function setQueue($queue)
    {
        $this->queue = $queue;

        return $this;
    }

    public function setConnection(QueueInterface $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    public function setSleep($seconds)
    {
        $this->sleep = $seconds;
    }

    public function start()
    {
        if (true === $this->daemonize) {
            $this->process->daemon();
        }

        return $this->process->start();
    }

    public function handle(swoole_process $worker)
    {
        process_rename($this->name);
        while (true) {
            list($payload, $reserved) = $this->connection->pop($this->queue);

            if (is_null($payload)) {
//                echo "sleeping... \n";
                sleep($this->sleep);
                continue;
            }

            $worker->push(json_encode([
                $payload, $reserved,
            ]));

            // TODO 当队列达到一定数量时, 暂停push
        }
    }
}
