<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */

namespace Runner\Queue;

class Job
{
    protected $maxRetries;

    protected $timeout;

    protected $attempts;

    protected $job;

    public function __construct($payload)
    {
        $json = json_decode($payload, true);
        $this->maxRetries = $json['max_retries'];
        $this->timeout = $json['timeout'];
        $this->attempts = $json['attempts'];
        $this->job = $json['job'];
    }

    public function generatePayload()
    {
        return json_encode([
            'id'          => $this->id,
            'max_retries' => $this->maxRetries,
            'timeout'     => $this->timeout,
            'attempts'    => $this->attempts,
            'job'         => $this->job,
        ]);
    }

    public function run()
    {
        unserialize($this->job)->run();
    }

    public function timeout()
    {
        return $this->timeout;
    }

    public function maxRetries()
    {
        return $this->maxRetries;
    }

    public function attempts()
    {
        return $this->attempts;
    }
}
