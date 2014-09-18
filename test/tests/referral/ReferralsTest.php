<?php

use Emailme\Currency\CurrencyUtil;
use Emailme\Debug\Debug;
use Emailme\Init\Environment;
use Emailme\Test\Account\AccountUtil;
use Emailme\Test\TestCase\SiteTestCase;
use Emailme\Test\Util\RequestUtil;
use \PHPUnit_Framework_Assert as PHPUnit;

/*
* 
*/
class ReferralsTest extends SiteTestCase
{


    public function testNewAccountReferredBy() {
        $app = Environment::initEnvironment('test');
        $account = AccountUtil::createNewAccount($app, ['referredBy' => 'mycode01']);

        PHPUnit::assertNotNull($account);
        PHPUnit::assertGreaterThan(1, strlen($account['id']));
        PHPUnit::assertGreaterThan(1, strlen($account['emailCanonical']));
        PHPUnit::assertGreaterThan(1, strlen($account['confirmToken']));
        PHPUnit::assertGreaterThan(time() - 10, $account['createdDate']);
        PHPUnit::assertEquals('mycode01', $account['referredBy']);
    }

    public function testAccountPaidIncrementsReferrerEarnings() {
        $app = Environment::initEnvironment('test');
        $source_account = AccountUtil::createNewAccount($app, []);

        $dest_account = AccountUtil::createNewAccount($app, ['bitcoinAddress'=>'DESTUSER01', 'referredBy' => $source_account['referralCode']]);
        $dest_account = $app['account.manager']->confirmAccount($dest_account);
        // test referred by
        PHPUnit::assertEquals($source_account['referralCode'], $dest_account['referredBy']);

        // mark dest account as paid
        $app['referral.manager']->handleNewPaidAccount($dest_account);

        // now check dest account balance
        $source_account = $source_account->reload();
        PHPUnit::assertEquals(CurrencyUtil::numberToSatoshis(100), $source_account['referralEarnings']);
        PHPUnit::assertEquals(1, $source_account['referralCount']);
    }



}

