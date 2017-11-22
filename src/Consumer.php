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
use Runner\Queue\Utils\Detectable;
use swoole_process;
use Throwable;

class Consumer extends Process
{
    use Detectable;

    /**
     * @var string
     */
    protected $queue;

    /**
     * @var QueueInterface
     */
    protected $connection;

    /**
     * @var bool
     */
    protected $running = false;

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
     * @param swoole_process $worker
     *
     * @return void
     */
    public function handle(swoole_process $worker)
    {
        while (true) {
            $this->running = false;
            $content = $worker->pop();

            if ('queue_shutdown' === $content) {
                swoole_process::kill($this->process->pid, SIGTERM);
            }

            list($payload, $reserved) = json_decode($content, true);

            $job = new Job($payload);

            if ($job->timeout()) {
                $this->registerTimeoutHandler($job->timeout());
            }

            try {
                $this->running = true;
                $job->run();
                $this->connection->deleteReserved($reserved, $this->queue);
            } catch (Exception $exception) {
                $this->handleJobException($job, $reserved);
            } catch (Throwable $exception) {
                $this->handleJobException($job, $reserved);
            }

            if ($job->timeout()) {
                $this->releaseTimeoutHandler();
            }

            unset($job, $content, $payload, $reserved);
        }
    }

    public function running()
    {
        return $this->running;
    }

    /**
     * @param Job $job
     * @param $reserved
     *
     * @return void
     */
    protected function handleJobException(Job $job, $reserved)
    {
        /*
         * 如果没有设置最大重试次数, 将一直重试
         */
        if (1 === $job->maxRetries() - $job->attempts()) {
            $this->connection->deleteReserved($reserved, $this->queue);
        }
    }

    /**
     * @param $timeout
     *
     * @return void
     */
    public function registerTimeoutHandler($timeout)
    {
        /*
         * 通过定时器实现超时自动杀进程, 由调度器重启进程
         * 注意, 要是在 Job 里面执行 exit 操作, 会触发超时杀进程
         */
        $this->signal(SIGALRM, function () {
            swoole_process::alarm(-1);
            $this->kill($this->process->pid, SIGKILL);
        });
        swoole_process::alarm(1000000 * $timeout);
    }

    /**
     * @return void
     */
    public function releaseTimeoutHandler()
    {
        swoole_process::alarm(-1);
    }
}
