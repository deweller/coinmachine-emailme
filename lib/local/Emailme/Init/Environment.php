<?php

namespace Emailme\Init;

use Emailme\Init\AppInit;
use \Exception;

/*
* Environment
*/
class Environment
{

    public static function initEnvironment($app_env=null, $config_version='shared') {
        if ($app_env === null) { $app_env = self::getEnvironment(); }
        $app = AppInit::initApp($app_env, $config_version);

        return $app;
    }


    public static function getEnvironment() {
        $app_env = getenv('APP_ENV');
        if ($app_env === null OR $app_env === false) { $app_env = 'prod'; }
        return $app_env;
    }


}
