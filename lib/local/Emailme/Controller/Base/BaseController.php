<?php

namespace Emailme\Controller\Base;

use Exception;
use Emailme\Debug\Debug;

/*
* BaseController
*/
class BaseController
{

    ////////////////////////////////////////////////////////////////////////

    public function __construct(\Silex\Application $app = null) {
        if ($app !== null) { $this->app = $app; }
    }

    ////////////////////////////////////////////////////////////////////////

}

