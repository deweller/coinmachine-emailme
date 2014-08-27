<?php

namespace Emailme\Init\DependencyInjections;


use Exception;
use Emailme\Debug\Debug;

/*
* JobsInit
*/
class JobsInit {

    public static function init($app) {
        self::initBeanstalk($app);
        self::initJobs($app);
    }



    public static function initBeanstalk($app) {
        $app['beanstalk.client'] = $app->share(function($app) {
            return new \Pheanstalk\Pheanstalk($app['config']['pheanstalk.host'], $app['config']['pheanstalk.port']);
        });

        $app['beanstalk.adderFactory'] = function($app) {
            return function($tube_name) use ($app) {
                return new \Emailme\Job\JobAdder($app['beanstalk.client'], $tube_name);
            };
        };
        $app['beanstalk.runner'] = function($app) {
            return new \Emailme\Job\JobQueueRunner($app['beanstalk.client'], $app);
        };
        $app['beanstalk.runnerFactory'] = function($app) {
            return function($tube_names) use ($app) {
                $runner = $app['beanstalk.runner'];
                $runner->watchTubes($tube_names);
                return $runner;
            };
        };
    }

    public static function initJobs($app) {
        $app['job.email'] = function($app) {
            return function($queue_entry) use ($app) {
                return new \Emailme\Job\Jobs\Email\EmailJob($queue_entry, $app['email.sender']);
            };
        };

    }


    ////////////////////////////////////////////////////////////////////////


}

