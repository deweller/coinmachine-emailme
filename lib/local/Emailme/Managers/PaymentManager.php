<?php

namespace Emailme\Managers;

use EmailMe\Debug\Debug;
use Utipd\CurrencyLib\CurrencyUtil;
use Emailme\EventLog\EventLog;
use Exception;

/*
* PaymentManager
*/
class PaymentManager
{

    const CONFIRMATIONS_REQUIRED = 3;

    ////////////////////////////////////////////////////////////////////////

    public function __construct($redis, $account_manager, $referral_manager, $prices) {
        $this->redis = $redis;
        $this->account_manager = $account_manager;
        $this->referral_manager = $referral_manager;
        $this->prices = $prices;

    }

    public function publishPaymentUpdate($account) {
        $balance = [];
        if (isset($account['balance'])) {
            foreach ($account['balance'] as $token => $amount) {
                // $balance[$token] = CurrencyUtil::satoshisToNumber($amount);
                $balance[$token] = $amount;
            }
        }

        $referral_earnings = $account['referralEarnings'];

        $vars = [
            'type'                   => 'payment',
            'balance'                => $balance,
            'referralEarnings'       => $referral_earnings,
            'isLifetime'             => !!$account['isLifetime'],
            'confirmationsToSendMap' => array_fill_keys($account['confirmationsToSend'], true),
            'notificationsRemaining' => $account['isLifetime'] ? 'unlimited' : $account['notificationsRemaining'],
        ];

        $this->redis->PUBLISH('account-'.$account['refId'], json_encode($vars));
    }


    public function updateAccountPaymentStatus($account, $all_transactions, $current_block_id) {
        // 'asset'          => $transaction['asset'],
        // 'quantity'       => $transaction['quantity'],

        $account = $account->reload();
        $before_data = (array)$account;

        // determine if we are all paid up
        $paid = null;

        // rebuild the balance from all transactions
        list($unconfirmed_balances, $confirmed_balances) = $this->rebuildBalances($account, $all_transactions, $current_block_id);
#      Debug::trace("updateAccountPaymentStatus account=".Debug::desc($account)." \$unconfirmed_balances=".Debug::desc($unconfirmed_balances)."  \$confirmed_balances=".Debug::desc($confirmed_balances)."",__FILE__,__LINE__,$this);

        $confirmed_paid = false;
        $paid = false;
        $required_btc_amount = CurrencyUtil::numberToSatoshis($this->prices['lifetime']['BTC']);
        $required_ltbcoin_amount = CurrencyUtil::numberToSatoshis($this->prices['lifetime']['LTBCOIN']);

        foreach($unconfirmed_balances as $asset => $amount) {
            switch ($asset) {
                case 'BTC':
                    if ($amount >= $required_btc_amount) {
                        $paid = true;
                        if (isset($confirmed_balances['BTC']) AND $confirmed_balances['BTC'] >= $required_btc_amount) {
                            $confirmed_paid = true;
                        }
                    }
                    break;

                case 'LTBCOIN':
                    if ($amount >= $required_ltbcoin_amount) {
                        $paid = true;
                        if (isset($confirmed_balances['LTBCOIN']) AND $confirmed_balances['LTBCOIN'] >= $required_ltbcoin_amount) {
                            $confirmed_paid = true;
                        }
                    }
                    break;
            }
        }

#       Debug::trace("\$unconfirmed_balances=\n".json_encode($unconfirmed_balances, 192),__FILE__,__LINE__,$this);


        // update the account
        $update_vars = [
            'balance'             => $unconfirmed_balances,
            'isLifetime'          => false,
            'isLifetimeConfirmed' => false,
        ];
        if ($paid === true) {
            $update_vars['isLifetime'] = true;
            $update_vars['confirmationsToSend'] = [0,3];
            if ($confirmed_paid === true) {
                $update_vars['isLifetimeConfirmed'] = true;
            }
        }

        $any_changes = false;
        if (json_encode($update_vars['balance']) != json_encode($before_data['balance'])) { $any_changes = true; }
        if ($update_vars['isLifetime'] != $before_data['isLifetime']) { $any_changes = true; }
        if ($update_vars['isLifetimeConfirmed'] != $before_data['isLifetimeConfirmed']) { $any_changes = true; }

        if ($any_changes) {
            $this->account_manager->update($account, $update_vars);

            if ($update_vars['isLifetimeConfirmed']) {
                $this->referral_manager->handleNewPaidAccount($account);
            }
        }

        return $any_changes;
    }

    ////////////////////////////////////////////////////////////////////////


    protected function rebuildBalances($account, $all_transactions, $current_block_id) {
        $balances = [];
        $confirmed_balances = [];
        foreach ($all_transactions as $transaction) {
            if ($transaction['destination'] == $account['paymentAddress']) {
                $asset = $transaction['asset'];
                $balances[$asset] = (isset($balances[$asset]) ? $balances[$asset] : 0) + $transaction['quantity'];

                $is_confirmed = false;
                if (!$transaction['isMempool']) {
#                    Debug::trace("transaction age is ".Debug::desc($current_block_id - $transaction['blockId'])."",__FILE__,__LINE__,$this);
                    if ($current_block_id - $transaction['blockId'] >= (self::CONFIRMATIONS_REQUIRED - 1)) {
                        $is_confirmed = true;
                    }
                }

                if ($is_confirmed) {
                    $confirmed_balances[$asset] = (isset($confirmed_balances[$asset]) ? $confirmed_balances[$asset] : 0) + $transaction['quantity'];
                }
            }
        }
        return [$balances, $confirmed_balances];
    }

}

