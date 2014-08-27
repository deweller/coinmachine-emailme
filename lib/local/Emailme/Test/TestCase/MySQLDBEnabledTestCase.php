<?php

namespace Emailme\Test\TestCase;

use Exception;
use Emailme\Debug\Debug;
use Emailme\Init\Environment;
use Emailme\Util\DB\TestDBUpdater;
use \PHPUnit_Framework_TestCase;

/*
* MySQLDBEnabledTestCase
*/
class MySQLDBEnabledTestCase extends PHPUnit_Framework_TestCase
{

    protected $cleanup = false;

    ////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////

    public function setup() {
        $app = Environment::initEnvironment('test');
        TestDBUpdater::prepCleanDatabase($app);
    }

    public function tearDown() {
        $app = Environment::initEnvironment('test');
        if ($this->cleanup) {
            TestDBUpdater::clearMySQLDBs($app);
        }
    }

}

