<?php

namespace Emailme\Init;

use Emailme\Debug\Debug;
use Emailme\EventLog\EventLog;
use Emailme\Init\DependencyInjections\ApplicationInit;
use Emailme\Init\DependencyInjections\DaemonInit;
use Emailme\Init\DependencyInjections\ControllersInit;
use Emailme\Init\DependencyInjections\EmailInit;
use Emailme\Init\DependencyInjections\JobsInit;
use Emailme\Init\DependencyInjections\ManagersInit;
use Emailme\Init\DependencyInjections\MysqlInit;
use Emailme\Init\DependencyInjections\RedisInit;
use Emailme\Init\DependencyInjections\UtilsInit;
use Emailme\Init\DependencyInjections\XCPDInit;
use Exception;

/*
* AppInit
*/
class AppInit
{

    public static function initApp($app_env=null, $config_location=null) {
        // init environment
        if ($app_env === null) { $app_env = getenv('APP_ENV') ?: 'prod'; }

        // build the silex app
        $app = new \Emailme\Application\Application();

        // environment
        $app['env'] = $app_env;

        // various dependency injections
        ApplicationInit::init($app);
        MysqlInit::init($app);
        ControllersInit::init($app);
        XCPDInit::init($app);
        ManagersInit::init($app);
        DaemonInit::init($app);
        UtilsInit::init($app);
        JobsInit::init($app);
        EmailInit::init($app);
        RedisInit::init($app);


        // special case for application-wide event log
        $app['event.log'] = $app->share(function($app) {
            return new EventLog($app['directory']('EventLog'), $app['config']['log.debug']);
        });
        EventLog::init($app['event.log']);

        return $app;
    }

}

