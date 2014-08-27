<?php

namespace Emailme\Job\Jobs\Email;

use Exception;
use Emailme\Debug\Debug;
use Emailme\Job\Jobs\BeanstalkJob;

/*
* EmailJob
*/
class EmailJob extends BeanstalkJob
{

     public function __construct($job_queue_entry, $email_sender) {
        parent::__construct($job_queue_entry);

        $this->email_sender = $email_sender;
    }


    ////////////////////////////////////////////////////////////////////////

    public function runJob($parameters) {
        // pay the hard credits from one user to another
        $response = $this->email_sender->sendEmailWithParams($parameters);
    }

    ////////////////////////////////////////////////////////////////////////

}

