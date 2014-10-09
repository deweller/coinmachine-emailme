<?php

namespace Emailme\Init\DependencyInjections;


use Exception;
use Emailme\Debug\Debug;

/*
* EmailInit
*/
class EmailInit {


    public static function init($app) {
        self::initEmailSender($app);
    }

    public static function initEmailSender($app) {
        $app['email.mandrill'] = function($app) {
            return new \Mandrill($app['config']['mandrill.apiKey']);
        } ;

        $app['email.sender.class'] = function($app) {
            if ($app['config']['email.test_mode']) { return "\Emailme\Test\Email\MockEmailSender"; }
            return "\Emailme\Email\EmailSender";
        };

        $app['email.sender'] = function($app) {
            return new $app['email.sender.class'](
                $app['email.mandrill'],
                $app['beanstalk.adderFactory']('email'),
                $app['email.twig'],
                $app['config']['email.defaults']
            );
        };

        $app['email.twig'] = function($app) {
            $twig = new \Twig_Environment(new \Twig_Loader_Filesystem(BASE_PATH.'/twig/email'));
            \Utipd\CurrencyLib\CurrencyUtil::addTwigFilters($twig);
            return $twig;
        };

    }


}

