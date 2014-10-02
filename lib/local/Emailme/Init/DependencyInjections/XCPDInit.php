<?php

namespace Emailme\Init\DependencyInjections;


use Exception;
use Emailme\Debug\Debug;

/*
* XCPDInit
*/
class XCPDInit {

    public static function init($app) {
        self::initXCPD($app);
        self::initNative($app);
        self::initFollowers($app);
        self::initAddresses($app);
        self::initXCPDSender($app);
        self::initPayer($app);
    }

    public static function initXCPDSender($app) {
        $app['xcp.sender'] = function($app) {
            return new \Utipd\CounterpartySender\CounterpartySender($app['xcpd.client'], $app['native.client']);
        };
    }

    public static function initXCPD($app) {

        $app['xcpd.connectionString'] = function($app) {
            return "{$app['config']['xcpd.scheme']}://{$app['config']['xcpd.host']}:{$app['config']['xcpd.port']}";

        };

        $app['xcpd.client'] = function($app) {
            return new \Utipd\XCPDClient\Client($app['xcpd.connectionString'], $app['config']['xcpd.rpcUser'], $app['config']['xcpd.rpcPassword']);
        };


    }
    public static function initNative($app) {

        $app['native.connectionString'] = function($app) {
            return "{$app['config']['native.scheme']}://{$app['config']['native.rpcUser']}:{$app['config']['native.rpcPassword']}@{$app['config']['native.host']}:{$app['config']['native.port']}";
        };

        $app['native.client'] = function($app) {
            return new \Nbobtc\Bitcoind\Bitcoind(new \Nbobtc\Bitcoind\Client($app['native.connectionString']));
        };


    }


    public static function initFollowers($app) {
        $app['mysql.xcpd.databaseName'] = function($app) {
            $prefix = $app['config']['mysqldb.prefix'] ?: 'emailme';
            return $prefix.'_xcpd_'.$app['config']['env'];
        };

        $app['xcpd.followerSetup'] = function($app) {
            return new \Utipd\CounterpartyFollower\FollowerSetup($app['mysql.client'], $app['mysql.xcpd.databaseName']);
        };

        $app['xcpd.follower'] = function($app) {
            $pdo = $app['mysql.client'];
            $pdo->query("use `".$app['mysql.xcpd.databaseName']."`");
            $follower = new \Utipd\CounterpartyFollower\Follower($app['xcpd.client'], $pdo);
            $follower->setGenesisBlock($app['config']['genesisBlockID']);
            return $follower;
        };


        $app['mysql.native.databaseName'] = function($app) {
            $prefix = $app['config']['mysqldb.prefix'] ?: 'emailme';
            return $prefix.'_native_'.$app['config']['env'];
        };

        $app['native.followerSetup'] = function($app) {
            return new \Utipd\NativeFollower\FollowerSetup($app['mysql.client'], $app['mysql.native.databaseName']);
        };

        $app['native.follower'] = function($app) {
            $pdo = $app['mysql.client'];
            $pdo->query("use `".$app['mysql.native.databaseName']."`");
            $follower = new \Utipd\NativeFollower\Follower($app['native.client'], $pdo);
            $follower->setGenesisBlock($app['config']['genesisBlockID']);
            return $follower;

        };


        $app['mysql.combined.databaseName'] = function($app) {
            $prefix = $app['config']['mysqldb.prefix'] ?: 'emailme';
            return $prefix.'_combined_'.$app['config']['env'];
        };

        $app['combined.followerSetup'] = function($app) {
            return new \Utipd\CombinedFollower\FollowerSetup($app['mysql.client'], $app['mysql.combined.databaseName']);
        };

        $app['mysql.combined.connectionManager'] = $app->share(function($app) {
            $manager = new \Utipd\MysqlModel\ConnectionManager($app['mysql.connection'].';dbname='.$app['mysql.databaseName'], $app['config']['mysql.username'], $app['config']['mysql.password']);
            $manager->getConnection()->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $manager->getConnection()->query("use `".$app['mysql.combined.databaseName']."`");
            return $manager;
        });

        $app['combined.follower'] = function($app) {
            $follower = new \Utipd\CombinedFollower\Follower($app['native.follower'], $app['xcpd.follower'], $app['mysql.combined.connectionManager']);
            $follower->setGenesisBlock($app['config']['genesisBlockID']);
            return $follower;
        };

    }


    public static function initAddresses($app) {

        $app['bitcoin.addressGenerator'] = function($app) {
            return new \Utipd\BitcoinAddressLib\BitcoinAddressGenerator($app['config']['bitcoin.masterKey']);
        };


    }

    public static function initPayer($app) {
        $app['bitcoin.payer'] = function($app) {
            return new \Utipd\BitcoinPayer\BitcoinPayer($app['native.client']);
        };
    }


}

