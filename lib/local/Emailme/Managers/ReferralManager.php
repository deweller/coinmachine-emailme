<?php

namespace Emailme\Managers;

use EmailMe\Debug\Debug;
use Emailme\Currency\CurrencyUtil;
use Emailme\EventLog\EventLog;
use Exception;

/*
* ReferralManager
*/
class ReferralManager
{

    ////////////////////////////////////////////////////////////////////////

    public function __construct($account_manager, $transaction_manager, $referral_amounts) {
        $this->referral_amounts = $referral_amounts;
        $this->transaction_manager = $transaction_manager;
        $this->account_manager = $account_manager;
    }

    public function handleNewPaidAccount($new_paid_account) {
        $referred_by = $new_paid_account['referredBy'];
        if (!$referred_by) { return; }

        $referring_account = $this->account_manager->findByReferralCode($referred_by);
        $amount = $this->determineReferringAmount($referring_account);
        if ($amount) {
            EventLog::logError('referral.amount', ['referringAccount' => $referring_account['id'], 'newAccount' => $new_paid_account['id'], 'amount' => $amount,]);
            $referring_account = $this->incrementReferringAccount($referring_account, $amount);
            EventLog::logError('referral.applied', ['referringAccount' => $referring_account['id'], 'newAccount' => $new_paid_account['id'], 'amount' => $amount, 'referralCount' => $referring_account['referralCount']]);
        } else {
            EventLog::logError('referral.amount.none', ['referringAccount' => $referring_account['id'], 'newAccount' => $new_paid_account['id']]);
        }

        return $referring_account;
    }

    ////////////////////////////////////////////////////////////////////////

    protected function determineReferringAmount($referring_account) {
        foreach ($this->referral_amounts as $amount_spec) {
            if ($referring_account['referralCount'] < $amount_spec['maxCount']) {
                return CurrencyUtil::numberToSatoshis($amount_spec['amount']);
            }
        }
        return CurrencyUtil::numberToSatoshis($amount_spec['amount']);
    }

    protected function incrementReferringAccount($referring_account, $amount) {
        $this->transaction_manager->doInTransaction(function() use ($referring_account, $amount) {
            $referring_account = $referring_account->reload();
            $update_vars = [
                'referralCount'    => $referring_account['referralCount'] + 1,
                'referralEarnings' => $referring_account['referralEarnings'] + $amount,
            ];

            $this->account_manager->update($referring_account, $update_vars);
        });

        return $referring_account->reload();
    }

}

