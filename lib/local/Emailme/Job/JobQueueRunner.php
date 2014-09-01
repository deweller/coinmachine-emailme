<?php

namespace Emailme\Job;

use Emailme\Debug\Debug;
use Emailme\EventLog\EventLog;
use Exception;

/*
* JobQueueRunner
*/
class JobQueueRunner
{

    protected $beanstalk_client = null;
    protected $tube_names = null;

    ////////////////////////////////////////////////////////////////////////

    public function __construct($beanstalk_client, $dependency_injector) {
        $this->beanstalk_client = $beanstalk_client;
        $this->dependency_injector = $dependency_injector;

    }

    public function watchTubes($tube_names) {
        $tube_names = is_array($tube_names) ? $tube_names : [$tube_names];
        $this->tube_names = $tube_names;
        return $this;
    }


    public function run() {
        // need to listen to signals, etc...
        $this->setWatch();

        while (true) {
            $job = $this->beanstalk_client->reserve();
            $this->runJob($job);
        }
    }

    public function setWatch() {
        if ($this->tube_names) {
            foreach ($this->tube_names as $tube_name) {
                $this->beanstalk_client->watch($tube_name);
            }
        }
    }


    public function runAllAndStop() {
#        Debug::trace("tube_names=".Debug::desc($this->tube_names)."",__FILE__,__LINE__,$this);
        $this->setWatch();

        while ($job = $this->beanstalk_client->reserve(0)) {
            $this->runJob($job);
        }
    }

    public function pullOneJobAndDeleteIt() {
        $this->setWatch();
        $job = $this->beanstalk_client->reserve(0);
        $this->beanstalk_client->delete($job);
        return $job;
    }

    public function clearAll() {
        // deletes all jobs

        $this->setWatch();

        // restore all buried jobs
        $this->beanstalk_client->kick(65536);

        // reserve all jobs and delete them
        while ($job = $this->beanstalk_client->reserve(0)) {
#            Debug::trace("draining job ".json_encode($job, 192),__FILE__,__LINE__,$this);
            $this->beanstalk_client->delete($job);
        }
    }

    ////////////////////////////////////////////////////////////////////////



    protected function runJob($job) {
        $queue_entry = json_decode($job->getData(), true);

#        Debug::trace("queue_entry=\n".json_encode($queue_entry, 192),__FILE__,__LINE__,$this);


        // build the job class using the dependency injector
        $instance = $this->dependency_injector[$queue_entry['jobType']]($queue_entry);

        try {
            // execute the job
            $result = $instance->execute();
            EventLog::logError('job.success', ['jobType' => $queue_entry['jobType'], 'result' => $result]);

            // success
            $this->beanstalk_client->delete($job);
        } catch (Exception $e) {
            EventLog::logError('job.failure', ['job' => $queue_entry, 'error' => $e]);

            // permanent failure
            $this->beanstalk_client->bury($job);
        }
    }
}

