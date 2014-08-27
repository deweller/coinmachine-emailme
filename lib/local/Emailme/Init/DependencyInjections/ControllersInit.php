<?php

namespace Emailme\Init\DependencyInjections;


use Exception;
use Emailme\Debug\Debug;

/*
* ControllersInit
*/
class ControllersInit {

    public static function init($app) {
        self::initControllers($app);
    }

    public static function initControllers($app) {

        $app['controller.home'] = function($app) {
            return new \Emailme\Controller\Site\Home\HomeController($app, $app['account.manager']);
        };

        $app['controller.plain'] = function($app) {

            return new \Emailme\Controller\Site\Plain\PlainController($app);
        };

        $app['controller.confirm'] = function($app) {
            return new \Emailme\Controller\Site\Confirm\ConfirmController($app, $app['account.manager']);
        };

        $app['controller.admin'] = function($app) {
            return new \Emailme\Controller\Site\Admin\AdminController($app, $app['directory']('EventLog'), $app['directory']('Account'));
        };

        $app['controller.account'] = function($app) {
            return new \Emailme\Controller\Site\Account\AccountController($app, $app['account.manager'], $app['payment.manager'], $app['notification.manager']);
        };

    }

}

