<?php

namespace Emailme\Init\DependencyInjections;


use Exception;
use Emailme\Debug\Debug;

/*
* ManagersInit
*/
class ManagersInit {

    public static function init($app) {
        self::initManagers($app);
    }

    public static function initManagers($app) {

        $app['account.defaults'] = function($app) {
            return $app['config']['account.defaults'];
        };

        $app['account.manager'] = function($app) {
            if ($app['env'] == 'test') {
                $native_client = null;
            } else {
                $native_client = $app['native.client'];
            }
            return new \Emailme\Managers\AccountManager($app['directory']('Account'), $app['token.generator'], $app['email.sender'], $app, $app['bitcoin.addressGenerator'], $app['combined.follower'], $app['account.defaults']);
        };

        $app['payment.manager'] = function($app) {
            return new \Emailme\Managers\PaymentManager($app['redis'], $app['account.manager'], $app['config']['prices']);
        };


        $app['notification.manager'] = function($app) {
            return new \Emailme\Managers\NotificationManager($app['redis'], $app['account.manager'], $app['directory']('Notification'), $app['assetBalanceBuilder']);
        };

        $app['assetBalanceBuilder'] = function($app) {
            $guzzle = new \GuzzleHttp\Client();
            return new \Emailme\Managers\Balance\AssetBalanceBuilder($app['xcpd.client'], $guzzle);
        };
    }






}

