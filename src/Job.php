<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */

namespace Runner\Queue;

class Job
{
    /**
     * @var integer
     */
    protected $maxRetries;

    /**
     * @var integer
     */
    protected $timeout;

    /**
     * @var integer
     */
    protected $attempts;

    /**
     * @var string
     */
    protected $job;

    /**
     * Job constructor.
     * @param string $payload
     */
    public function __construct($payload)
    {
        $json = json_decode($payload, true);
        $this->maxRetries = $json['max_retries'];
        $this->timeout = $json['timeout'];
        $this->attempts = $json['attempts'];
        $this->job = $json['job'];
    }

    /**
     * @return string
     */
    public function generatePayload()
    {
        return json_encode([
            'max_retries' => $this->maxRetries,
            'timeout'     => $this->timeout,
            'attempts'    => $this->attempts,
            'job'         => $this->job,
        ]);
    }

    /**
     * @return void
     */
    public function run()
    {
        unserialize($this->job)->run();
    }

    /**
     * @return int
     */
    public function timeout()
    {
        return $this->timeout;
    }

    /**
     * @return int
     */
    public function maxRetries()
    {
        return $this->maxRetries;
    }

    /**
     * @return int
     */
    public function attempts()
    {
        return $this->attempts;
    }
}
