<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2017-09
 */

namespace Runner\Queue\Utils;

use swoole_process;

trait Detectable
{
    public function started()
    {
        return $this->process->pid ? swoole_process::kill($this->process->pid, 0) : false;
    }
}
