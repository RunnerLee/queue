<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017 - 11
 */
use Runner\Queue\Contracts\JobInterface;

class Demo implements JobInterface
{
    /**
     * @return void
     */
    public function run()
    {
        file_get_contents('https://www.baidu.com');

        $arr = [];

        for ($i = 0; $i < 4; $i++) {
            $arr[] = range(0, random_int(100, 200));
        }

        file_put_contents(__DIR__.'/queue.log', "1\n", FILE_APPEND);
    }
}
