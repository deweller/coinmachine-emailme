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
class CreateAccountTest extends SiteTestCase
{

    public function testNewAccountErrors() {
        $app = Environment::initEnvironment('test');

        $submission_vars = array_merge(AccountUtil::newAccountVars(), ['email' => '']);
        RequestUtil::assertResponseWithStatusCode($app, 'POST', '/', $submission_vars, 200, 'Please enter a valid email address.');

        $submission_vars = array_merge(AccountUtil::newAccountVars(), ['bitcoinAddress' => 'BADBADADDRESS']);
        RequestUtil::assertResponseWithStatusCode($app, 'POST', '/', $submission_vars, 200, 'Address must be a valid Bitcoin address');


        // all ok
        $submission_vars = array_merge(AccountUtil::newAccountVars(), []);
        RequestUtil::assertResponseWithStatusCode($app, 'POST', '/', $submission_vars, 303);
    } 

    public function testNewAccountCreated() {
        $app = Environment::initEnvironment('test');
        $account = AccountUtil::createNewAccount($app);

        PHPUnit::assertNotNull($account);
        PHPUnit::assertGreaterThan(1, strlen($account['id']));
        PHPUnit::assertGreaterThan(1, strlen($account['emailCanonical']));
        PHPUnit::assertGreaterThan(1, strlen($account['confirmToken']));
        PHPUnit::assertGreaterThan(time() - 10, $account['createdDate']);
    }


    public function testConfirmAccount() {
        $app = Environment::initEnvironment('test');
        $account = AccountUtil::createNewAccount($app);

        PHPUnit::assertNotNull($account);

        $account = $app['account.manager']->confirmAccount($account);

        $account = $account->reload();
        PHPUnit::assertNotNull($account);
        PHPUnit::assertGreaterThan(1, strlen($account['paymentAddress']));
        PHPUnit::assertGreaterThan(1, strlen($account['paymentAddressToken']));
    }



}

