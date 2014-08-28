<?php

namespace Emailme\Job;

use Exception;
use Emailme\Debug\Debug;

/*
* JobAdder
*/
class JobAdder
{

    ////////////////////////////////////////////////////////////////////////

    public function __construct($beanstalk_client, $tube_name) {
        $this->beanstalk_client = $beanstalk_client;
        $this->tube_name = $tube_name;
    }

    public function addJob($job_type, $parameters) {
        // Debug::trace("addJob \$job_type=".Debug::desc($job_type)." \$this->tube_name=".Debug::desc($this->tube_name)."",__FILE__,__LINE__,$this);
        return $this->beanstalk_client->useTube($this->tube_name)->put(json_encode([
            'jobType'  => $job_type,
            'parameters' => $parameters,
        ]));
    }

    ////////////////////////////////////////////////////////////////////////

}

