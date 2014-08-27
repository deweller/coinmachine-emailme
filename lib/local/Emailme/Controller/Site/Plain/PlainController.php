<?php

namespace Emailme\Controller\Site\Plain;

use Exception;
use Emailme\Controller\Site\Base\BaseSiteController;
use Emailme\Debug\Debug;

/*
* PlainController
*/
class PlainController extends BaseSiteController
{

    ////////////////////////////////////////////////////////////////////////

    public function renderPlainTemplate($template, $twig_vars=[]) {
        return $this->renderTwig($template, $twig_vars);
    }

    ////////////////////////////////////////////////////////////////////////

}

