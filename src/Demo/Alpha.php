<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */

namespace Runner\Queue\Demo;

use Runner\Queue\Contracts\JobInterface;

class Alpha implements JobInterface
{

    protected $data = [
        'a' => 'b',
    ];

    public function run()
    {
        echo "I'm alpha\n";
        print_r($this->data);
        echo "\n";
    }
}
