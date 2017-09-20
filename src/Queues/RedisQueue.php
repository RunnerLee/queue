<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */

namespace Runner\Queue\Queues;

use Predis\Client;
use Runner\Queue\Contracts\QueueInterface;
use Runner\Queue\RedisLuaScripts;

class RedisQueue implements QueueInterface
{
    /**
     * @var Client
     */
    protected $connector;

    /**
     * @var int
     */
    protected $retryAfter;

    /**
     * RedisQueue constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $config['persistent'] = true;
        $this->connector = new Client($config);
    }

    /**
     * @param int $seconds
     *
     * @return static
     */
    public function setRetryAfter($seconds)
    {
        $this->retryAfter = $seconds;

        return $this;
    }

    /**
     * @param string $queue
     *
     * @return array
     */
    public function pop($queue)
    {
        $this->migrate($queue);

        return $this->connector->eval(
            RedisLuaScripts::pop(),
            2,
            $queue, "{$queue}:reserved",
            time() + $this->retryAfter
        );
    }

    /**
     * @param string $jobPayload
     * @param string $queue
     *
     * @return void
     */
    public function push($jobPayload, $queue)
    {
        $this->connector->rpush($queue, $jobPayload);
    }

    /**
     * @param string $jobPayload
     * @param int    $timestamp
     * @param string $queue
     *
     * @return void
     */
    public function pushAt($jobPayload, $timestamp, $queue)
    {
        $this->connector->zadd("{$queue}:delayed", [
            $timestamp => $jobPayload,
        ]);
    }

    /**
     * @param string $jobPayload
     * @param string $queue
     *
     * @return void
     */
    public function deleteReserved($jobPayload, $queue)
    {
        $this->connector->zrem("{$queue}:reserved", $jobPayload);
    }

    /**
     * @param string $queue
     *
     * @return void
     */
    protected function migrate($queue)
    {
        $this->connector->eval(RedisLuaScripts::migrate(), 2, "{$queue}:reserved", $queue, time());
        $this->connector->eval(RedisLuaScripts::migrate(), 2, "{$queue}:delayed", $queue, time());
    }
}
