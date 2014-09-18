<?php

namespace Emailme\Daemon;

use EmailMe\Debug\Debug;
use Emailme\EventLog\EventLog;
use Emailme\EventLog\logEvent;
use Exception;

/*
* BlockchainDaemon
*/
class BlockchainDaemon
{

    ////////////////////////////////////////////////////////////////////////

    public function __construct($combined_follower, $simple_daemon_factory, $account_manager, $payment_manager, $notification_manager) {
        $this->combined_follower     = $combined_follower;
        $this->simple_daemon_factory = $simple_daemon_factory;
        $this->account_manager       = $account_manager;
        $this->payment_manager       = $payment_manager;
        $this->notification_manager  = $notification_manager ;
    }

    public function setupAndRun() {
        $this->setup();
        $this->run();
    }

    public function setup() {
        $this->setupFollowerCallbacks();
    }

    public function run() {
        EventLog::logEvent('daemon.start', []);

        $f = $this->simple_daemon_factory;
        $iteration_count = 0;

        $loop_function = function() use (&$iteration_count) {
            $this->runOneIteration();
            ++$iteration_count;

            // restart about every 5 minutes
            if ($iteration_count > 60) { throw new Exception("forcing process restart", 250); }
        };

        $error_handler = function($e) use (&$iteration_count) {
            if ($e->getCode() == 250) {
                // force restart
                throw $e;
            }

            EventLog::logError('daemon.error', $e);

            // restart about every 5 minutes
            ++$iteration_count;
            if ($iteration_count > 60) { throw new Exception("forcing process restart", 250); }
        };

        $daemon = $f($loop_function, $error_handler);

        try {
            $daemon->run();
        } catch (Exception $e) {
            if ($e->getCode() == 250) {
                EventLog::logEvent('daemon.shutdown', ['reason' => $e->getMessage()]);
            } else { 
                EventLog::logError('daemon.error.final', $e);
            }
        }

        EventLog::logEvent('daemon.shutdown', []);
    }

    public function runOneIteration() {
#        Debug::trace("begin runOneIteration",__FILE__,__LINE__,$this);
        $this->combined_follower->runOneIteration();
#        Debug::trace("end runOneIteration",__FILE__,__LINE__,$this);
    }





    ////////////////////////////////////////////////////////////////////////
    // sends

    protected function handleIncomingSendTransaction($transaction, $number_of_confirmations, $current_block_id) {
        $destination_address = $transaction['destination'];
        $account = $this->account_manager->findByBitcoinAddress($destination_address);
        if ($account) {
            $this->processSendToAccount($account, $transaction, $number_of_confirmations, $current_block_id);
        }
    }

    protected function processSendToAccount($account, $transaction, $number_of_confirmations, $current_block_id) {
        if ($account->isActive()) {
            if ($this->sendIsFromSelf($account, $transaction)) {
                EventLog::logEvent('send.ignored', ['accountId' => $account['id'], 'asset' => $transaction['asset'], 'tx' => $transaction, 'mempool' => $transaction['isMempool'], 'blockId' => $current_block_id]);
            } else {
                EventLog::logEvent('send.received', ['accountId' => $account['id'], 'asset' => $transaction['asset'], 'tx' => $transaction, 'mempool' => $transaction['isMempool'], 'blockId' => $current_block_id]);

                // notify
                $this->notification_manager->handleNewSendToAccount($account, $transaction, $number_of_confirmations, $current_block_id);
            }
        } else {

            // this account is inactive
            EventLog::logEvent('send.received.inactive', ['accountId' => $account['id'], 'asset' => $transaction['asset'], 'tx' => $transaction, 'mempool' => $transaction['isMempool'], 'blockId' => $current_block_id]);
        }
    }

    protected function sendIsFromSelf($account, $transaction) {
        $sources = $transaction['source'];
        if ($sources AND !is_array($sources)) { $sources = [$sources]; }
        if ($sources) {
            foreach($sources as $source) {
                if ($source == $account['bitcoinAddress']) {
                    return true;
                }
            }
        }
        return false;
    }

    ////////////////////////////////////////////////////////////////////////
    // payments

    protected function handleIncomingPaymentTransaction($transaction, $current_block_id) {
        $destination_address = $transaction['destination'];
        $account = $this->account_manager->findByPaymentAddress($destination_address);
        if ($account) {
            $this->processPaymentToAccount($account, $transaction, $this->combined_follower->allTransactionsToDestination($destination_address), $current_block_id);
        }
    }

    protected function processPaymentToAccount($account, $transaction, $all_transactions, $current_block_id) {
        if ($transaction['asset'] == 'LTBCOIN' OR $transaction['asset'] == 'BTC') {
            EventLog::logEvent('payment.received', ['accountId' => $account['id'], 'asset' => $transaction['asset'], 'tx' => $transaction, 'mempool' => $transaction['isMempool']]);
        } else { 
            EventLog::logEvent('payment.received.unknown', ['accountId' => $account['id'], 'asset' => $transaction['asset'], 'tx' => $transaction, 'mempool' => $transaction['isMempool']]);
        }

        $any_changes = $this->payment_manager->updateAccountPaymentStatus($account, $all_transactions, $current_block_id);

        if ($any_changes) {
            // and publish
            $account = $account->reload();
            $this->payment_manager->publishPaymentUpdate($account);

            // also publish to referrer account
            if (strlen($account['referredBy']) AND $referrer_account = $this->account_manager->findByReferralCode($account['referredBy'])) {
                $this->payment_manager->publishPaymentUpdate($referrer_account);
            }
        }
    }

    protected function updateAllUnconfirmedPayments($current_block_id) {
        foreach ($this->account_manager->find(['isLifetime' => 1, 'isLifetimeConfirmed' => 0]) as $unconfirmed_account) {
            // possibly confirm this account
            $all_transactions = $this->combined_follower->allTransactionsToDestination($unconfirmed_account['paymentAddress']);
            $this->payment_manager->updateAccountPaymentStatus($unconfirmed_account, $all_transactions, $current_block_id);
        }
    }


    ////////////////////////////////////////////////////////////////////////

    protected function setupFollowerCallbacks() {

        $this->combined_follower->handleMempoolTransaction(function($transaction, $current_block_id) {
#            Debug::trace("handleMempoolTransaction \$transaction=\n".json_encode($transaction, 192),__FILE__,__LINE__,$this);
            $this->handleIncomingPaymentTransaction($transaction, $current_block_id);
            $this->handleIncomingSendTransaction($transaction, 0, $current_block_id);
        });

        $this->combined_follower->handleConfirmedTransaction(function ($transaction, $number_of_confirmations, $current_block_id) {
#            Debug::trace("handleConfirmedTransaction \$transaction=\n".json_encode($transaction, 192),__FILE__,__LINE__,$this);
            $this->handleIncomingPaymentTransaction($transaction, $current_block_id);
            $this->handleIncomingSendTransaction($transaction, $number_of_confirmations, $current_block_id);
        });

        $this->combined_follower->handleNewNativeBlock(function ($block_id) {
            EventLog::logEvent('native.block.found', ['blockId' => $block_id,]);
            $this->updateAllUnconfirmedPayments($block_id);
        });

        $this->combined_follower->handleNewCounterpartyBlock(function ($block_id) {
            EventLog::logEvent('counterparty.block.found', ['blockId' => $block_id,]);
            $this->updateAllUnconfirmedPayments($block_id);
        });
    }

    // // list($accounts_by_monitor_address, $accounts_by_payment_address) = $this->allAccountsByAddress();
    // protected function allAccountsByAddress() {
    //     $accounts_by_monitor_address = [];
    //     $accounts_by_payment_address = [];
    //     foreach ($this->account_manager->allAccounts() as $account) {
    //         $accounts_by_monitor_address[$account['bitcoinAddress']] = $account;
    //         $accounts_by_payment_address[$account['paymentAddress']] = $account;
    //     }
    //     return [$accounts_by_monitor_address, $accounts_by_payment_address];
    // }


}

