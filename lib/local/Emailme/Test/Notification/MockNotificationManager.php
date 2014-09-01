<?php

namespace Emailme\Test\Notification;

use Emailme\Debug\Debug;
use Emailme\Managers\NotificationManager;
use Exception;

/*
* MockNotificationManager
*/
class MockNotificationManager extends NotificationManager
{

    static $NOTIFICATIONS_SENT;
    static $JOB_QUEUE_RUNNER;

    public static function addNotification($notification) {
        if (!isset(self::$NOTIFICATIONS_SENT)) { self::$NOTIFICATIONS_SENT = []; }
        self::$NOTIFICATIONS_SENT[] = $notification;
    }

    public static function getNotifications() {
        if (!isset(self::$NOTIFICATIONS_SENT)) { self::$NOTIFICATIONS_SENT = []; }
        return self::$NOTIFICATIONS_SENT;
    }

    public static function clearNotifications() {
        self::$NOTIFICATIONS_SENT = [];
    }

    public static function setJobQueueRunner($job_queue_runner) {
        self::$JOB_QUEUE_RUNNER = $job_queue_runner;
    }

    ////////////////////////////////////////////////////////////////////////

    public function sendNotification($account, $transaction, $confirmations, $current_block_id, $asset_balance) {
        // send the email
        parent::sendNotification($account, $transaction, $confirmations, $current_block_id, $asset_balance);

        // pull the email contents from the job queue
        $email = [];
        if (self::$JOB_QUEUE_RUNNER) {
            $job = self::$JOB_QUEUE_RUNNER->pullOneJobAndDeleteIt();
            if ($job) {
                $queue_entry = json_decode($job->getData(), true);
                if ($queue_entry AND $queue_entry['jobType'] == 'job.email') {
                    $email = $queue_entry['parameters'];
                }
            }
        }

        self::addNotification([
            'account'       => $account,
            'transaction'   => $transaction,
            'confirmations' => $confirmations,
            'blockId'       => $current_block_id,
            'assetBalance'  => $asset_balance,
            'email'         => $email,
        ]);



        // parent::sendNotification($account, $transaction, $confirmations, $current_block_id);
        return;
    }



}

