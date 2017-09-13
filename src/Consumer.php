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

class Consumer extends Process
{
    protected $queue;

    /**
     * @var QueueInterface
     */
    protected $connection;

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
            $content = $worker->pop();

            if ('queue_shutdown' === $content) {
                echo "consumer {$this->process->pid} exit \n";
                exit(0);
            }

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

    protected function handleJobException(Job $job, $reserved)
    {
        if (1 === $job->maxRetries() - $job->attempts()) {
            $this->connection->deleteReserved($reserved, $this->queue);
        }
    }

    public function registerTimeoutHandler($timeout)
    {
        $this->signal(SIGALRM, function () {
            $this->kill($this->process->pid, SIGKILL);
        });
        swoole_process::alarm(1000000 * $timeout);
    }

    public function releaseTimeoutHandler()
    {
        swoole_process::alarm(-1);
    }
}
