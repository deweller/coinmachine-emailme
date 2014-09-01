<?php

namespace Emailme\Managers;

use EmailMe\Debug\Debug;
use Emailme\Bitcoin\BitcoinAmountTool;
use Emailme\Currency\CurrencyUtil;
use Emailme\EventLog\EventLog;
use Exception;

/*
* NotificationManager
*/
class NotificationManager
{

    ////////////////////////////////////////////////////////////////////////

    public function __construct($redis, $account_manager, $notification_directory, $asset_balance_builder) {
        $this->redis                  = $redis;
        $this->account_manager        = $account_manager;
        $this->notification_directory = $notification_directory;
        $this->asset_balance_builder  = $asset_balance_builder;
    }

    public function publishNotificationUpdate($account) {
        $vars = [
            'type'          => 'notifications',
            'notifications' => $this->sanitizeNotificationsForPublishing($this->allNotifications($account)),
        ];

        $this->redis->PUBLISH('account-'.$account['refId'], json_encode($vars));
    }

    public function handleNewSendToAccount($account, $transaction, $number_of_confirmations, $current_block_id) {
#        Debug::trace("\handleNewSendToAccount \$account=".Debug::desc($account)." shouldSend: ".Debug::desc($this->shouldSendNotification($account, $transaction, $number_of_confirmations))."",__FILE__,__LINE__,$this);
        if ($this->shouldSendNotification($account, $transaction, $number_of_confirmations)) {
            // load the account balance
            $asset_balance = $this->asset_balance_builder->getAssetBalance($transaction, $number_of_confirmations);

                // if this is a mempool transaction, add the pending transaction quantity
            if ($number_of_confirmations == 0) {
                $asset_balance = $asset_balance + $transaction['quantity'];
            }

            $this->sendNotification($account, $transaction, $number_of_confirmations, $current_block_id, $asset_balance);

            // mark the notification as sent
            $this->updateAccountNotificationsRemaining($account);

            // save notification
            try {
                $notification = $this->saveNotification($account, $transaction, $number_of_confirmations, $current_block_id);
                EventLog::logEvent('notification.saved', ['accountId' => $account['id'], 'notification' => $notification]);
            } catch (Exception $e) {
                EventLog::logEvent('notification.saved.error', ['accountId' => $account['id'], 'error' => $e]);
            }

            // publish the notification
            $this->publishNotificationUpdate($account);
        }
    }


    public function updateAccountNotificationsRemaining($account) {
        if (!$account->isLifetime()) {
            $account = $account->reload();
            $notifications_remaining = $account['notificationsRemaining'] - 1;
            $account = $this->account_manager->update($account, ['notificationsRemaining' => $notifications_remaining]);
        }

        return $account;
    }

    public function saveNotification($account, $transaction, $number_of_confirmations, $current_block_id) {
        return $this->notification_directory->createAndSave([
            'accountId'     => $account['id'],
            'tx_hash'       => $transaction['tx_hash'],
            'isNative'      => $transaction['isNative'],
            'confirmations' => $number_of_confirmations,
            'sentDate'      => time(),
            'blockId'       => $current_block_id,
            'tx'            => $transaction,
        ]);
    }

    public function findHighestNotificationSent($account, $transaction) {
        return $this->notification_directory->findOne(['accountId' => $account['id'], 'tx_hash' => $transaction['tx_hash']], ['confirmations' => -1]);
    }

    public function sendNotification($account, $transaction, $confirmations, $current_block_id, $asset_balance) {

        $response = $this->account_manager->sendEmail('notification/paid-notification', $account, [
            'transaction'        => $transaction,
            'blockId'            => $current_block_id,
            'confirmations'      => $confirmations,
            'accountDetailsLink' => $this->account_manager->generateAccountDetailsLink($account),
            'currentBalance'     => $asset_balance,
        ]);

        EventLog::logEvent('email.notification', ['accountId' => $account['id'], 'confirmations' => $confirmations, 'response' => $response]);

        // $email_params = $this->email_sender->composeEmailParametersFromTemplate($email_name, array_merge(['account' => $account], $other_vars));
        // $this->email_sender->sendEmailInBackgroundWithParameters($email_params);

    }


    public function allNotifications($account) {
        return $this->notification_directory->find(['accountId' => $account['id']], ['sentDate' => -1]);
    }

    ////////////////////////////////////////////////////////////////////////

    protected function shouldSendNotification($account, $transaction, $number_of_confirmations_occurred) {
#        Debug::trace("\$account->isActive()=".Debug::desc($account->isActive())."",__FILE__,__LINE__,$this);
        if ($account->isActive()) {
            if ($account['confirmationsToSend'] AND in_array($number_of_confirmations_occurred, $account['confirmationsToSend'])) {
                return true;
            }

            $highest_confirmation_already_sent = -1;
            $notification = $this->findHighestNotificationSent($account, $transaction);
            if ($notification) {
                $highest_confirmation_already_sent = $notification['confirmations'];
            }

            // see if we skipped one
            foreach (array_reverse($account['confirmationsToSend']) as $confirmation_to_send) {
#               Debug::trace("\$account={$account['id']} \$confirmation_to_send=$confirmation_to_send \$number_of_confirmations_occurred=$number_of_confirmations_occurred \$highest_confirmation_already_sent=$highest_confirmation_already_sent",__FILE__,__LINE__,$this);
                // if this confirmation hasn't happened yet, skip it
                if ($confirmation_to_send > $number_of_confirmations_occurred) { continue; }

                // this confirmation should have happened - if it hasn't, send it now
                if ($confirmation_to_send > $highest_confirmation_already_sent) {
#                   Debug::trace("TRUE!",__FILE__,__LINE__,$this);
                    return true;
                }
            }
        }

        return false;
    }       


    protected function sanitizeNotificationsForPublishing($notifications) {
        $notifications_out = [];
        foreach($notifications as $notification) {
            unset($notification['id']);
            unset($notification['accountId']);
            unset($notification['tx']['id']);
            unset($notification['tx']['transactionId']);

            $notifications_out[] = $notification;
        }
        return $notifications_out;
        // code
    }

}

