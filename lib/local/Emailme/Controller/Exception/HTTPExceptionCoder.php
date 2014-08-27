<?php

namespace Emailme\Controller\Exception;

use Exception;
use Emailme\Debug\Debug;

/*
* HTTPExceptionCoder
*/
interface HTTPExceptionCoder
{

    ////////////////////////////////////////////////////////////////////////

    // public function __construct() {
    // }

    public function setHTTPErrorCode($http_error_code);
    public function getHTTPErrorCode();

    ////////////////////////////////////////////////////////////////////////

}

