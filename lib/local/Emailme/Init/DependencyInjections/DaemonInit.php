<?php

namespace Emailme\Init\DependencyInjections;

use Emailme\Debug\Debug;
use Emailme\Init\Controller\ControllerResolver;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Utipd\CurrencyLib\CurrencyUtil;

/*
* DaemonInit
*/
class DaemonInit {

    public static function init($app) {
        self::initDaemon($app);
    }

    public static function initDaemon($app) {
#        Debug::trace("initDaemon",__FILE__,__LINE__);

        $app['blockchain.daemon'] = function($app) {
            return new \Emailme\Daemon\BlockchainDaemon($app['combined.follower'], $app['simpleDaemon'], $app['account.manager'], $app['payment.manager'], $app['notification.manager']);
        };

        $app['simpleDaemon'] = function($app) {
            return function($loop_function, Callable $error_handler=null) use ($app) {
                return new \Utipd\SimpleDaemon\Daemon($loop_function, $error_handler, $app['monolog']);
            };
        };


    }


}

