<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */

namespace Runner\Queue\Contracts;

interface QueueInterface
{
    /**
     * @param string $queue
     *
     * @return array
     */
    public function pop($queue);

    /**
     * @param string $jobPayload
     * @param $queue
     *
     * @return null|void
     */
    public function push($jobPayload, $queue);

    /**
     * @param string $jobPayload
     * @param int    $timestamp
     * @param string $queue
     *
     * @return null|void
     */
    public function pushAt($jobPayload, $timestamp, $queue);

    /**
     * @param string $jobPayload
     * @param string $queue
     *
     * @return null|void
     */
    public function deleteReserved($jobPayload, $queue);

    /**
     * @param int $seconds
     *
     * @return static
     */
    public function setRetryAfter($seconds);
}
