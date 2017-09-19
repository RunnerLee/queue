<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */

use Runner\Queue\Contracts\JobInterface;

class Alpha implements JobInterface
{

    /**
     * @return void
     */
    public function run()
    {
        echo "I'm alpha\n";
    }
}
