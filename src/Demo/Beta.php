<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */

namespace Runner\Queue\Demo;

use Runner\Queue\Contracts\JobInterface;

class Beta implements JobInterface
{

    protected $data = [
        'c' => 'd',
    ];

    public function run()
    {

    }
}
