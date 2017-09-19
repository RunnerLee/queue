<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */

use Runner\Queue\Contracts\JobInterface;

class Beta implements JobInterface
{

    /**
     * @return void
     */
    public function run()
    {
        echo "I'm beta\n";
        sleep(20);
//        throw new Exception('fuck');
    }
}
