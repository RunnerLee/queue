<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */

namespace Runner\Queue;

use Runner\Queue\Contracts\QueueInterface;
use Swoole\Process;
use Exception;
use Throwable;

class Consumer
{

    protected $worker;

    protected $queue;

    protected $pid;

    protected $connection;

    public function __construct($swooleQueueKey, $queue, QueueInterface $connection)
    {
        $this->worker = new Process([$this, 'doConsumer']);

        $this->worker->useQueue($swooleQueueKey);

        $this->queue = $queue;

        $this->connection = $connection;
    }

    public function run() {
        return $this->pid = $this->worker->start();
    }

    public function pid()
    {
        return $this->pid;
    }

    public function doConsumer(Process $worker)
    {
        swoole_set_process_name('queue consumer');
        while (true) {
            $content = $worker->pop();
            list($payload, $reserved) = json_decode($content, true);

            $job = new Job($payload);

            $this->registerTimeoutHandler($job->timeout());

            try {
                $job->run();
                $this->connection->deleteReserved($reserved, $this->queue);
            } catch (Exception $exception) {
                $this->handleJobException($job, $reserved);
            } catch (Throwable $exception) {
                $this->handleJobException($job, $reserved);
            }
            $this->releaseTimeoutHandler();
        }
    }

    protected function runJob($payload)
    {
        (new Job($payload))->run();
    }

    protected function handleJobException(Job $job, $reserved)
    {
        if (1 === $job->maxRetries() - $job->attempts()) {
            $this->connection->deleteReserved($reserved, $this->queue);
        }
    }

    public function registerTimeoutHandler($timeout)
    {
        Process::signal(SIGALRM, function () {
            Process::kill($this->worker->pid, SIGKILL);
        });

        Process::alarm(1000000 * $timeout);
    }

    public function releaseTimeoutHandler()
    {
        Process::alarm(-1);
    }
}
