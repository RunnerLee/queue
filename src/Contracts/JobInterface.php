<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */

namespace Runner\Queue\Contracts;

interface JobInterface
{
    /**
     * @return void
     */
    public function run();
}
