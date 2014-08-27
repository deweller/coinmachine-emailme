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

    ////////////////////////////////////////////////////////////////////////

    public function sendNotification($account, $transaction, $confirmations, $current_block_id) {
        self::addNotification([
            'account'       => $account,
            'transaction'   => $transaction,
            'confirmations' => $confirmations,
            'blockId'       => $current_block_id,
        ]);

        // parent::sendNotification($account, $transaction, $confirmations, $current_block_id);
        return;
    }



}

