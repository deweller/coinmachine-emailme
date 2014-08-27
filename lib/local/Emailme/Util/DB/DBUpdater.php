<?php

namespace Emailme\Util\DB;

use Exception;
use Emailme\Debug\Debug;
use Emailme\Util\Twig\TwigUtil;

/*
* DBUpdater
*/
class DBUpdater
{

    ////////////////////////////////////////////////////////////////////////


    public static function bringDatabaseUpToDate($app) {
        // update SQL tables
        $dbh = $app['mysql.client'];
        $sql = TwigUtil::renderTwigText(file_get_contents(BASE_PATH.'/etc/sql/tables.mysql'), ['app' => $app]);
        $result = $dbh->exec($sql);

        // also update the counterparty follower tables
        $app['xcpd.followerSetup']->InitializeDatabase();
        $app['native.followerSetup']->InitializeDatabase();
        $app['combined.followerSetup']->InitializeDatabase();

        // create system users
        // $app['user.manager']->createMissingSystemUsers();
    }



    ////////////////////////////////////////////////////////////////////////

}

