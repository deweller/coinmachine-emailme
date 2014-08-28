<?php

namespace Emailme\Job\Jobs;

use Exception;
use Emailme\Debug\Debug;

/*
* BeanstalkJob
*/
class BeanstalkJob
{

    ////////////////////////////////////////////////////////////////////////

    public function __construct($job_queue_entry) {
        $this->job_queue_entry = $job_queue_entry;
    }

    public function execute() {
        try {
            return $this->runJob($this->job_queue_entry['parameters']);
        } catch (Exception $e) {
            Debug::errorTrace("ERROR: ".$e->getMessage(),__FILE__,__LINE__,$this);

            // cleanup
            EventLog::logError('job.error', $e);

            throw $e;
        }
    }

/*
    public function runJob($parameters) {
        try {
            // job here
            
        } catch (Exception $e) {
            Debug::errorTrace("ERROR: ".$e->getMessage(),__FILE__,__LINE__,$this);
            throw $e;
        }
    }
*/

    ////////////////////////////////////////////////////////////////////////

}

