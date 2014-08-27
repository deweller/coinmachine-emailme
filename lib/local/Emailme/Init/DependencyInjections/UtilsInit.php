<?php

namespace Emailme\Init\DependencyInjections;


use Exception;
use Emailme\Debug\Debug;

/*
* UtilsInit
*/
class UtilsInit {

    public static function init($app) {
        self::initUtils($app);
    }

    public static function initUtils($app) {

        $app['token.generator'] = function($app) {
            return new \Emailme\Authentication\Token\TokenGenerator();
        };

    }






}


