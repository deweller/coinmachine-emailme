<?php

use Emailme\Debug\Debug;
use Emailme\Init\Environment;
use Emailme\Test\Account\AccountUtil;
use Emailme\Test\TestCase\SiteTestCase;
use Emailme\Test\Util\RequestUtil;
use \PHPUnit_Framework_Assert as PHPUnit;

/*
* 
*/
class LiveEmailConfirmationTest extends SiteTestCase
{


    public function testLiveEmailConfirmation() {
        $app = Environment::initEnvironment('test');
        $job_runner = $app['beanstalk.runnerFactory'](['email']);

        // we need to route in order to generate email URLs
        $app['router.site']->route();

        // clear jobs
        $job_runner->clearAll();

        // override mock sender to send live email
        // $app['email.sender.class'] = function($app) { return "\Emailme\Email\EmailSender"; };

        // send the email (in background)
        $account = AccountUtil::createNewAccount($app);
        $app['account.manager']->sendConfirmationEmail($account);

        // run jobs
        $job_runner->runAllAndStop();
    }



}

