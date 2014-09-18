<?php

namespace Emailme\Managers;

use EmailMe\Debug\Debug;
use Emailme\EventLog\EventLog;
use Exception;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/*
* AccountManager
*/
class AccountManager
{

    ////////////////////////////////////////////////////////////////////////

    public function __construct($account_directory, $token_generator, $email_sender, $url_generator, $bitcoin_address_generator, $combined_follower, $account_defaults) {
        $this->account_directory = $account_directory;
        $this->token_generator = $token_generator;
        $this->email_sender = $email_sender;
        $this->account_defaults = $account_defaults;
        $this->url_generator = $url_generator;
        $this->bitcoin_address_generator = $bitcoin_address_generator;
        $this->combined_follower = $combined_follower;
    }

    public function newAccount($new_account_vars) {
        $account_vars = $new_account_vars;
        foreach ($this->account_defaults as $field_name => $default) {
            if (!isset($account_vars[$field_name])) {
                $account_vars[$field_name] = $default;
            }
        }

        // emailCanonical
        if (isset($account_vars['email'])) {
            $account_vars['emailCanonical'] = trim(strtolower($account_vars['email']));
        }

        // confirmToken
        if (!isset($account_vars['confirmToken'])) { $account_vars['confirmToken'] = $this->token_generator->generateToken('CONFIRMATION'); }

        // createdDate
        if (!isset($account_vars['createdDate'])) { $account_vars['createdDate'] = time(); }

        // balance
        if (!isset($account_vars['balance'])) { $account_vars['balance'] = []; }

        // referral
        if (!isset($account_vars['referralCode'])) { $account_vars['referralCode'] = $this->token_generator->generateToken('REFERRAL', 9); }
        if (!isset($account_vars['referralEarnings'])) { $account_vars['referralEarnings'] = 0; }
        if (!isset($account_vars['referralCount'])) { $account_vars['referralCount'] = 0; }

        return $this->account_directory->createAndSave($account_vars);
    }

    public function sendConfirmationEmail($account) {
        $this->sendEmail('confirm/confirm', $account, [
            'confirmLink' => $this->url_generator->url('account-confirm', ['token' => $account['confirmToken']], UrlGeneratorInterface::ABSOLUTE_URL)
        ]);
    }
    public function sendAccountDetailsLink($account) {
        $this->sendEmail('account/account-details-link', $account, [
            'accountDetailsLink' => $this->generateAccountDetailsLink($account),
        ]);
    }

    public function generateAccountDetailsLink($account) {
        return $this->url_generator->url('account-details', ['refId' => $account['refId']], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function sendEmail($email_name, $account, $other_vars=[]) {
        $email_params = $this->email_sender->composeEmailParametersFromTemplate($email_name, array_merge(['account' => $account], $other_vars));
        return $this->email_sender->sendEmailInBackgroundWithParameters($email_params);
    }

    public function allAccounts() {
        return $this->find([]);
    }

    public function find($vars, $sort=null, $limit=null) {
        return $this->account_directory->find($vars, $sort, $limit);
    }

    public function findByConfirmToken($token) {
        return $this->account_directory->findOne(['confirmToken' => $token]);
    }

    public function findByRefId($ref_id) {
        return $this->account_directory->findOne(['refId' => $ref_id]);
    }

    public function findByBitcoinAddressAndEmail($address, $email) {
        return $this->account_directory->findOne(['bitcoinAddress' => $address, 'emailCanonical' => trim(strtolower($email))]);
    }

    public function findByPaymentAddress($payment_address) {
        return $this->account_directory->findOne(['paymentAddress' => $payment_address]);
    }
    public function findByBitcoinAddress($bitcoin_address) {
        return $this->account_directory->findOne(['bitcoinAddress' => $bitcoin_address]);
    }
    public function findByReferralCode($referral_code) {
        return $this->account_directory->findOne(['referralCode' => $referral_code]);
    }

    public function update($account, $vars) {
        $this->account_directory->update($account, $vars);
        return $account->reload();
    }


    public function confirmAccount($account) {
        $update_vars = [];

        if (!isset($account['refId']) OR !strlen($account['refId'])) {
            $update_vars['refId'] = $this->token_generator->generateToken('ACCOUNT');
        }
        if (!isset($account['isConfirmed']) OR !$account['isConfirmed']) {
            $update_vars['isConfirmed'] = true;
        }
        if (!isset($account['confirmedDate']) OR !$account['confirmedDate']) {
            $update_vars['confirmedDate'] = time();
        }

        if (!isset($account['paymentAddress']) OR !$account['paymentAddress']) {
            $update_vars['paymentAddressToken'] = $this->token_generator->generateToken('ADDRESS');
            $update_vars['paymentAddress'] = $this->bitcoin_address_generator->publicAddress($update_vars['paymentAddressToken']);
        }

        if ($update_vars) {
            $this->account_directory->update($account, $update_vars);
            $account = $account->reload();
        }

        // add this account to the list of watch addresses
        $this->combined_follower->addAddressToWatch($account['paymentAddress']);
        $this->combined_follower->addAddressToWatch($account['bitcoinAddress']);

        return $account;
    }

    ////////////////////////////////////////////////////////////////////////

}

