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

foreach ($app['directory']('Notification')->find([], ['sentDate' => 1]) as $notification) {
    print
        '['.$notification['id']."][".$notification['tx']['transactionId']."] ".date("Y-m-d H:i:s", $notification['sentDate']).": ".CurrencyUtil::satoshisToNumber($notification['tx']['quantity'])." (".$notification['tx']['quantity'].") ".$notification['tx']['asset'].
        "\n";
}