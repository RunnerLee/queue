<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */

namespace Runner\Queue;

use Runner\Queue\Contracts\QueueInterface;
use Swoole\Process;

class Producer
{
    protected $worker;

    protected $queue;

    protected $connection;

    public function __construct($swooleQueueKey, $queue, QueueInterface $connection)
    {
        $this->worker = new Process([$this, 'doProducer']);
        $this->worker->useQueue($swooleQueueKey);
        $this->queue = $queue;
        $this->connection = $connection;
    }

    public function run()
    {
        $this->worker->start();
    }

    public function doProducer(Process $worker)
    {
        swoole_set_process_name('queue producer');
        while (1) {
            list ($payload, $reserved) = $this->connection->pop($this->queue);

            if (is_null($payload)) {
//                echo "sleeping...\n";
                sleep(2);
                continue;
            }

            $worker->push(json_encode([
                $payload, $reserved
            ]));
        }
    }
}
