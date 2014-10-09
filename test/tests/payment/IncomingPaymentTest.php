<?php

use Utipd\CurrencyLib\CurrencyUtil;
use Emailme\Debug\Debug;
use Emailme\Init\Environment;
use Emailme\Test\Account\AccountUtil;
use Emailme\Test\BlockchainDaemon\BlockchainDaemonHandler;
use Emailme\Test\TestCase\SiteTestCase;
use Emailme\Test\Util\RequestUtil;
use \PHPUnit_Framework_Assert as PHPUnit;

/*
* 
*/
class IncomingPaymentTest extends SiteTestCase
{

    public function testPaymentReceivedNotEnough() {
        $app = Environment::initEnvironment('test');

        // create an account
        $account = AccountUtil::createNewAccount($app);
        $account = $app['account.manager']->confirmAccount($account);

        // process a blockchain transaction
        $mock_blockchain_handler = new BlockchainDaemonHandler($this, $app);

        // insert a sample counterparty transaction
        $sent_data = $mock_blockchain_handler->sendMockCounterpartyTransaction($account, ['address' => $account['paymentAddress'], 'quantity' => CurrencyUtil::numberToSatoshis(999)]);

        // make sure we are not paid up yet
        $account = $account->reload();

        PHPUnit::assertFalse(!!$account['isLifetime']);
    }


    public function testPaymentReceivedEnough() {
        $app = Environment::initEnvironment('test');

        // create an account
        $account = AccountUtil::createNewAccount($app);
        $account = $app['account.manager']->confirmAccount($account);

        // process a blockchain transaction
        $mock_blockchain_handler = new BlockchainDaemonHandler($this, $app);

        // insert a sample counterparty transaction
        $sent_data = $mock_blockchain_handler->sendMockCounterpartyTransaction($account, ['address' => $account['paymentAddress'], 'quantity' => CurrencyUtil::numberToSatoshis(999)]);
        $sent_data = $mock_blockchain_handler->sendMockCounterpartyTransaction($account, ['address' => $account['paymentAddress'], 'quantity' => CurrencyUtil::numberToSatoshis(1)]);

        // make sure we are paid up
        $account = $account->reload();

        PHPUnit::assertTrue(!!$account['isLifetime']);
    }


    public function testBTCPaymentReceivedIsNotEnough() {
        $app = Environment::initEnvironment('test');

        // create an account
        $account = AccountUtil::createNewAccount($app);
        $account = $app['account.manager']->confirmAccount($account);

        // process a blockchain transaction
        $mock_blockchain_handler = new BlockchainDaemonHandler($this, $app);

        // insert a sample native transaction
        // amount, address, blockId, is_mempool
        $sent_data = $mock_blockchain_handler->sendMockNativeTransaction($account, ['address' => $account['paymentAddress'], 'amount' => CurrencyUtil::numberToSatoshis($app['config']['prices']['lifetime']['BTC'] - 0.00001)]);

        // make sure we are not paid up yet
        $account = $account->reload();

        PHPUnit::assertFalse(!!$account['isLifetime']);
    }


    public function testBTCPaymentReceivedEnough() {
        $app = Environment::initEnvironment('test');

        // create an account
        $account = AccountUtil::createNewAccount($app);
        $account = $app['account.manager']->confirmAccount($account);

        // process a blockchain transaction
        $mock_blockchain_handler = new BlockchainDaemonHandler($this, $app);

        // insert a sample native transaction
        // amount, address, blockId, is_mempool
        $sent_data = $mock_blockchain_handler->sendMockNativeTransaction($account, ['address' => $account['paymentAddress'], 'amount' => CurrencyUtil::numberToSatoshis($app['config']['prices']['lifetime']['BTC'])]);

        // make sure we are paid up
        $account = $account->reload();

        PHPUnit::assertTrue(!!$account['isLifetime']);
    }

    public function testBTCPaymentIsConfirmed() {
        $app = Environment::initEnvironment('test');

        // create an account
        $account = AccountUtil::createNewAccount($app);
        $account = $app['account.manager']->confirmAccount($account);

        // process a blockchain transaction
        $mock_blockchain_handler = new BlockchainDaemonHandler($this, $app);

        // insert a sample native transaction
        // amount, address, blockId, is_mempool
        $sent_data = $mock_blockchain_handler->sendMockNativeTransaction($account, ['address' => $account['paymentAddress'], 'amount' => CurrencyUtil::numberToSatoshis($app['config']['prices']['lifetime']['BTC'])]);

        // make sure we are not paid up yet
        $account = $account->reload();
        PHPUnit::assertTrue(!!$account['isLifetime']);
        PHPUnit::assertFalse(!!$account['isLifetimeConfirmed']);

        // not yet...
        $sent_data = $mock_blockchain_handler->processNativeBlock(6001);

        // make sure we are not paid up yet
        $account = $account->reload();
        PHPUnit::assertTrue(!!$account['isLifetime']);
        PHPUnit::assertFalse(!!$account['isLifetimeConfirmed']);


        // now confirm it
        $sent_data = $mock_blockchain_handler->processNativeBlock(6002);

        // make sure we are paid up
        $account = $account->reload();
        PHPUnit::assertTrue(!!$account['isLifetime']);
        PHPUnit::asserttrue(!!$account['isLifetimeConfirmed']);
    }


    public function testLTBCoinPaymentIsConfirmed() {
        $app = Environment::initEnvironment('test');

        // create an account
        $account = AccountUtil::createNewAccount($app);
        $account = $app['account.manager']->confirmAccount($account);

        // process a blockchain transaction
        $mock_blockchain_handler = new BlockchainDaemonHandler($this, $app);

        // insert a sample native transaction
        // amount, address, blockId, is_mempool
        $sent_data = $mock_blockchain_handler->sendMockCounterpartyTransaction($account, ['address' => $account['paymentAddress'], 'amount' => CurrencyUtil::numberToSatoshis(1000)]);

        // make sure we are not paid up yet
        $account = $account->reload();
        PHPUnit::assertTrue(!!$account['isLifetime']);
        PHPUnit::assertFalse(!!$account['isLifetimeConfirmed']);

        // not yet...
        $mock_blockchain_handler->processNativeBlock(6001);
        $mock_blockchain_handler->processCounterpartyBlock(6001);

        // make sure we are not paid up yet
        $account = $account->reload();
        PHPUnit::assertTrue(!!$account['isLifetime']);
        PHPUnit::assertFalse(!!$account['isLifetimeConfirmed']);


        // now confirm it
        $mock_blockchain_handler->processNativeBlock(6002);
        $mock_blockchain_handler->processCounterpartyBlock(6002);

        // make sure we are paid up
        $account = $account->reload();
        PHPUnit::assertTrue(!!$account['isLifetime']);
        PHPUnit::asserttrue(!!$account['isLifetimeConfirmed']);
    }


    public function testDoubleSpend() {
        $app = Environment::initEnvironment('test');

        // create an account
        $account = AccountUtil::createNewAccount($app);
        $account = $app['account.manager']->confirmAccount($account);

        // process a blockchain transaction
        $mock_blockchain_handler = new BlockchainDaemonHandler($this, $app);

        // we need at least one native block
        $sent_data = $mock_blockchain_handler->processNativeBlock(6000);

        // insert a sample native transaction
        // amount, address, blockId, is_mempool
        $sent_data = $mock_blockchain_handler->sendMockNativeTransaction($account, ['address' => $account['paymentAddress'], 'amount' => CurrencyUtil::numberToSatoshis($app['config']['prices']['lifetime']['BTC']), 'is_mempool' => true]);

        // make sure we are not paid up yet
        $account = $account->reload();

        PHPUnit::assertTrue(!!$account['isLifetime']);
        PHPUnit::assertFalse(!!$account['isLifetimeConfirmed']);

        // now wipe out with bad a confirmed transaction
        $sent_data = $mock_blockchain_handler->processNativeBlock(6001);
        // $sent_data = $mock_blockchain_handler->sendMockNativeTransaction($account, ['address' => '1otheraddress', 'amount' => CurrencyUtil::numberToSatoshis(0.002), 'is_mempool' => false, 'blockId' => 6001]);

        // fix isLifetime
        $account = $account->reload();
        PHPUnit::assertFalse(!!$account['isLifetime']);
        PHPUnit::assertFalse(!!$account['isLifetimeConfirmed']);

        
    }



        // // now handle block 6001
        // $sent_data = $mock_blockchain_handler->processCounterpartyBlock(6001);

        // // there should be only 1 blockchain transaction now
        // PHPUnit::assertCount(1, iterator_to_array($blockchain_tx_dir->findAll()));

        // // remaining tx is not a mempool transaction
        // $blockchain_tx = $blockchain_tx_dir->findOne([]);
        // PHPUnit::assertEquals(0, $blockchain_tx['isMempool']);

    // }



}

