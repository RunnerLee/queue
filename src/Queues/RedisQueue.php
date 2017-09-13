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
    protected $connector;

    protected $retryAfter;

    public function __construct($config, $retryAfter = 60)
    {
        $config['persistent'] = true;
        $this->connector = new Client($config);
        $this->retryAfter = $retryAfter;
    }

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

    public function push($jobPayload, $queue)
    {
        $this->connector->rpush($queue, $jobPayload);
    }

    public function pushAt($jobPayload, $timestamp, $queue)
    {
        $this->connector->zadd("{$queue}:delayed", [
            $timestamp => $jobPayload,
        ]);
    }

    public function deleteReserved($jobPayload, $queue)
    {
        $this->connector->zrem("{$queue}:reserved", $jobPayload);
    }

    protected function migrate($queue)
    {
        $this->connector->eval(RedisLuaScripts::migrate(), 2, "{$queue}:reserved", $queue, time());
        $this->connector->eval(RedisLuaScripts::migrate(), 2, "{$queue}:delayed", $queue, time());
    }
}
