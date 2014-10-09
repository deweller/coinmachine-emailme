<?php

namespace Emailme\Daemon;

use EmailMe\Debug\Debug;
use Emailme\EventLog\EventLog;
use Exception;

/*
* HealthCheckDaemon
*/
class HealthCheckDaemon
{

    protected $recipients = [['Devon Weller','dweller@devonweller.com']];

    ////////////////////////////////////////////////////////////////////////

    public function __construct($health_checker, $email_sender, $simple_daemon_factory) {
        $this->health_checker        = $health_checker;
        $this->email_sender          = $email_sender;
        $this->simple_daemon_factory = $simple_daemon_factory;

        $this->iteration_count = 0;
        $this->last_email_sent_time = 0;
    }

    public function setupAndRun() {
        // setup?

        // run
        $this->run();
    }

    public function run() {
        EventLog::logEvent('healthcheck.daemon.start', []);

        $f = $this->simple_daemon_factory;
        $iteration_count = 0;

        $loop_function = function() use (&$iteration_count) {
            $this->runOneIteration();
            $this->iterate();
        };

        $error_handler = function($e) use (&$iteration_count) {
            if ($e->getCode() == 250) {
                // force restart
                throw $e;
            }

            EventLog::logError('healthcheck.daemon.error', $e);

            $this->iterate();
        };

        $daemon = $f($loop_function, $error_handler);

        $daemon->setLoopInterval(30);

        try {
            $daemon->run();
            EventLog::logEvent('healthcheck.daemon.shutdown', []);
        } catch (Exception $e) {
            if ($e->getCode() == 250) {
                EventLog::logEvent('healthcheck.daemon.shutdown', ['reason' => $e->getMessage()]);
            } else { 
                EventLog::logError('healthcheck.daemon.error.final', $e);
            }
        }

    }

    public function runOneIteration() {
        $results = $this->health_checker->checkHealth();

        $any_problems = false;
        foreach($results as $result) {
            if (!$result['alive']) {
                $any_problems = true;
                break;
            }
        }

        if ($any_problems) {
            $this->handleFailure($results);
        }
    }


    ////////////////////////////////////////////////////////////////////////

    protected function handleFailure($results) {
        if (time() - $this->last_email_sent_time > 600) {
            // log failure
            $failed_daemons = [];
            foreach($results as $result) {
                if (!$result['alive']) { $failed_daemons[] = $result['name']; }
            }
            EventLog::logEvent('healthcheck.failed', ['failedDaemons' => $failed_daemons]);

            // send email
            $this->sendEmail($results);

            // once every 10 min
            $this->last_email_sent_time = time();

        }
    }

    protected function sendEmail($results) {
        $subject = "Daemon Health Check Failed";
        $message_text = date("Y-m-d H:i:s T")."\n\nResults:\n".json_encode($results, 192)."\n";
        $other = [
            'from_name'  => 'Healthcheck Daemon',
            'from_email' => 'no-reply@coinmachine.co',
        ];

        foreach($this->recipients as $recipient) {
#            Debug::trace("sending email: $recipient[0], $recipient[1], $subject, ".Debug::desc($message_text)."",__FILE__,__LINE__,$this);
            $this->email_sender->sendEmail($recipient[0], $recipient[1], $subject, $message_text, null, $other);
        }

    }

    protected function iterate() {
        ++$this->iteration_count;

        // restart about every 5 minutes
        if ($this->iteration_count > 60) { throw new Exception("forcing process restart", 250); }
    }

}

