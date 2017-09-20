<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */

namespace Runner\Queue;

use Runner\Queue\Queues\RedisQueue;

class QueueFactory
{

    protected $config;

    protected $connections = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function connection($driver)
    {
        if (!isset($this->connections[$driver])) {
            switch ($driver) {
                case 'redis':
                    $this->connections[$driver] = new RedisQueue($this->config[$driver]);
                    break;
            }
        }
        return $this->connections[$driver];
    }
}
