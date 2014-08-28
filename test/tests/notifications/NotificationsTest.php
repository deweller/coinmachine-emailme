<?php

use Emailme\Currency\CurrencyUtil;
use Emailme\Debug\Debug;
use Emailme\Init\Environment;
use Emailme\Test\Account\AccountUtil;
use Emailme\Test\BlockchainDaemon\BlockchainDaemonHandler;
use Emailme\Test\Notification\MockNotificationManager;
use Emailme\Test\TestCase\SiteTestCase;
use Emailme\Test\Util\RequestUtil;
use \PHPUnit_Framework_Assert as PHPUnit;

/*
* 
*/
class NotificationsTest extends SiteTestCase
{

    public function testMempoolNotification() {
        $app = Environment::initEnvironment('test');
        $this->initMockNotificationManager($app);

        // create an account
        $account = AccountUtil::createNewLifetimeConfirmedAccount($app);

        // build handler
        $mock_blockchain_handler = new BlockchainDaemonHandler($this, $app);

        // insert a sample native transaction
        $sent_data = $mock_blockchain_handler->sendMockNativeMempoolTransaction($account, ['address' => $account['bitcoinAddress'], 'amount' => CurrencyUtil::numberToSatoshis(0.500)]);

        // test that we were notified
        $notifications = MockNotificationManager::getNotifications();
        PHPUnit::assertCount(1, $notifications);
        PHPUnit::assertEquals($account['bitcoinAddress'], $notifications[0]['transaction']['destination']);
        PHPUnit::assertEquals(CurrencyUtil::numberToSatoshis(0.500), $notifications[0]['transaction']['quantity']);
        PHPUnit::assertEquals(0, $notifications[0]['confirmations']);
        PHPUnit::assertEquals(6000, $notifications[0]['blockId']);
    }


    public function testThreeConfirmationsNotification() {
        $app = Environment::initEnvironment('test');
        // we need to route in order to generate email URLs
        $app['router.site']->route();
        $this->initMockNotificationManager($app);

        // create an account
        $account = AccountUtil::createNewLifetimeConfirmedAccount($app, ['confirmationsToSend' => [3]]);

        // build handler
        $mock_blockchain_handler = new BlockchainDaemonHandler($this, $app);

        // process a block
        $mock_blockchain_handler->processAllBlocks(6000);

        // insert a sample native transaction
        $sent_data = $mock_blockchain_handler->sendMockNativeTransaction($account, ['address' => $account['bitcoinAddress'], 'amount' => CurrencyUtil::numberToSatoshis(0.500)]);

        // process three more blocks
        $mock_blockchain_handler->processAllBlocks(6001);
        $mock_blockchain_handler->processAllBlocks(6002);
        $mock_blockchain_handler->processAllBlocks(6003);


        // test that we were notified
        $notifications = MockNotificationManager::getNotifications();
        PHPUnit::assertCount(1, $notifications);
        PHPUnit::assertEquals($account['bitcoinAddress'], $notifications[0]['transaction']['destination']);
        PHPUnit::assertEquals(3, $notifications[0]['confirmations']);
        PHPUnit::assertEquals(6002, $notifications[0]['blockId']);
    }


    public function testCombinedNativeAndCounterpartyMempoolTransaction() {
        $app = Environment::initEnvironment('test');
        $this->initMockNotificationManager($app);

        // create an account
        $account = AccountUtil::createNewLifetimeConfirmedAccount($app);

        // build handler
        $mock_blockchain_handler = new BlockchainDaemonHandler($this, $app);

        // send a sample native transaction and counterparty transaction
        $sent_data = $mock_blockchain_handler->sendMockNativeMempoolTransaction($account, ['address' => $account['bitcoinAddress'], 'amount' => CurrencyUtil::numberToSatoshis(0.000078)]);
        $sent_data = $mock_blockchain_handler->sendMockCounterpartyMempoolTransaction($account, ['destination' => $account['bitcoinAddress'], 'quantity' => CurrencyUtil::numberToSatoshis(100), 'asset' => 'LTBCOIN']);

        // test that we were notified
        $notifications = MockNotificationManager::getNotifications();
        PHPUnit::assertCount(1, $notifications);
        PHPUnit::assertEquals($account['bitcoinAddress'], $notifications[0]['transaction']['destination']);
        PHPUnit::assertEquals('LTBCOIN', $notifications[0]['transaction']['asset']);
        PHPUnit::assertEquals(CurrencyUtil::numberToSatoshis(100), $notifications[0]['transaction']['quantity']);
        PHPUnit::assertEquals(0, $notifications[0]['confirmations']);
        PHPUnit::assertEquals(6000, $notifications[0]['blockId']);
    }

    public function testSkippedMempoolNotificationIsStillSent() {
        $app = Environment::initEnvironment('test');
        $this->initMockNotificationManager($app);

        // create an account
        $account = AccountUtil::createNewLifetimeConfirmedAccount($app);

        // build handler
        $mock_blockchain_handler = new BlockchainDaemonHandler($this, $app);

        // process a block
        $mock_blockchain_handler->processAllBlocks(6000);

        // insert a sample native transaction
        $sent_data = $mock_blockchain_handler->sendMockNativeTransaction($account, ['address' => $account['bitcoinAddress'], 'amount' => CurrencyUtil::numberToSatoshis(0.500)]);

        // process three more blocks
        $mock_blockchain_handler->processAllBlocks(6001);

        // test that we were notified even though the mempool was missed
        $notifications = MockNotificationManager::getNotifications();
        PHPUnit::assertCount(1, $notifications);
        PHPUnit::assertEquals(1, $notifications[0]['confirmations']);
    }

    public function testIgnoreSendFromSelf() {
        $app = Environment::initEnvironment('test');
        $this->initMockNotificationManager($app);

        // create an account
        $account = AccountUtil::createNewLifetimeConfirmedAccount($app);

        // build handler
        $mock_blockchain_handler = new BlockchainDaemonHandler($this, $app);

        // insert a sample native transaction
        $sent_data = $mock_blockchain_handler->sendMockNativeMempoolTransaction($account, ['input_address' => $account['bitcoinAddress'], 'address' => $account['bitcoinAddress'], 'amount' => CurrencyUtil::numberToSatoshis(0.500)]);

        // test that we were NOT notified
        $notifications = MockNotificationManager::getNotifications();
        PHPUnit::assertCount(0, $notifications);
    }



    ////////////////////////////////////////////////////////////////////////

    protected function initMockNotificationManager($app) {
        MockNotificationManager::clearNotifications();
        $app['notification.manager'] = function($app) {
            return new \Emailme\Test\Notification\MockNotificationManager($app['redis'], $app['account.manager'], $app['directory']('Notification'));
        };
    }


}

