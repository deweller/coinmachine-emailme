<?php

use Utipd\CurrencyLib\CurrencyUtil;
use Emailme\Debug\Debug;
use Emailme\Init\Environment;
use Emailme\Test\TestCase\SiteTestCase;
use \PHPUnit_Framework_Assert as PHPUnit;

/*
* 
*/
class AccountBalanceTest extends SiteTestCase
{

    public function testLiveAccountBalances() {
        // don't run by default
        if (getenv('DO_LIVE_TESTS') == false) { $this->markTestIncomplete(); }

        $app = Environment::initEnvironment('test');

        // live balance (XCP asset)
        $balance_in_satoshis = $app['assetBalanceBuilder']->getAssetBalance('16H6FULzgpiru8dFoX1kyYjmqsFgBF29Dp', 'LTBCOIN');
        PHPUnit::assertEquals(CurrencyUtil::numberToSatoshis(2550000), $balance_in_satoshis);

        // live balance (BTC)
        $balance_in_satoshis = $app['assetBalanceBuilder']->getAssetBalance('18i7EZqzBwiqAEKitGEumn73aKajsPzUuF', 'BTC');
        PHPUnit::assertEquals(CurrencyUtil::numberToSatoshis(0.000978), $balance_in_satoshis);
    }


    ////////////////////////////////////////////////////////////////////////


}

