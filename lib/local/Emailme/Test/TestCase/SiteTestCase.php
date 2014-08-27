<?php

namespace Emailme\Test\TestCase;

use Exception;
use Emailme\Debug\Debug;
use Emailme\Init\Environment;
use Emailme\Util\DB\TestDBUpdater;
use \PHPUnit_Framework_TestCase;

/*
* SiteTestCase
*/
class SiteTestCase extends MySQLDBEnabledTestCase
{

    ////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////

    public function setup() {
        parent::setup();
    }

    public function tearDown() {
        parent::tearDown();
    }

}

