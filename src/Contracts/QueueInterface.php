<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */

namespace Runner\Queue\Contracts;

interface QueueInterface
{
    public function pop($queue);

    public function push($jobPayload, $queue);

    public function pushAt($jobPayload, $timestamp, $queue);

    public function deleteReserved($jobPayload, $queue);
}
