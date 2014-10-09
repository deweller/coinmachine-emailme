#!/usr/local/bin/php
<?php 

declare(ticks=1);

use Utipd\CurrencyLib\CurrencyUtil;
use Emailme\Init\Environment;

define('BASE_PATH', realpath(__DIR__.'/../..'));
require BASE_PATH.'/lib/vendor/autoload.php';


// specify the spec as human readable text and run validation and help:
$values = CLIOpts\CLIOpts::run("
    Usage: 
    -h, --help show this help
");

$app = Environment::initEnvironment();

$notification_dir = $app['directory']('Notification');
foreach ($notification_dir->find([], ['sentDate' => 1]) as $notification) {
   print
        '['.$notification['id']."][".$notification['tx']['transactionId']."] ".date("Y-m-d H:i:s", $notification['sentDate']).": ".CurrencyUtil::satoshisToNumber($notification['tx']['quantity'])." (".$notification['tx']['quantity'].") ".$notification['tx']['asset'].
        "\n";

    if ($notification['tx']['quantity'] < 10) {
        // fix quantity
        $tx = $notification['tx'];
        $tx['quantity'] = CurrencyUtil::numberToSatoshis($tx['quantity']);
        $update_vars = ['tx' => $tx];
        // echo "\$update_vars:\n".json_encode($update_vars, 192)."\n";
        echo "Updating {$notification['id']}\n";
        $notification_dir->update($notification, $update_vars);
    }
}