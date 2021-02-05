<?php

namespace tests\jobs;

class MyFailingJob extends \flusio\jobs\Job
{
    public function perform(...$args)
    {
        throw new \Exception('I failed you :(');
    }
}
