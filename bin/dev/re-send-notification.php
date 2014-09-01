#!/usr/local/bin/php
<?php 


use BitWasp\BitcoinLib\BIP32;
use BitWasp\BitcoinLib\BitcoinLib;
use Emailme\Auctioneer\Payer\BTCSweeper;
use Emailme\Currency\CurrencyUtil;
use Emailme\Init\Environment;
use Emailme\Util\Params\ParamsUtil;


define('BASE_PATH', realpath(__DIR__.'/../..'));
require BASE_PATH.'/lib/vendor/autoload.php';

$values = CLIOpts\CLIOpts::run("
  Usage: 
  -i <id> Notification id (required)
  -h, --help show this help
");

$app = Environment::initEnvironment();
echo "Environment: ".$app['config']['env']."\n";

// route for email URLs
$app['router.site']->route();

$notification_dir = $app['directory']('Notification');
$account_dir = $app['directory']('Account');
$notification_manager = $app['notification.manager'];


$notification = $notification_dir->findByID($values['i']);
if (!$notification) { throw new Exception("Notification not found", 1); }

$account = $account_dir->findByID($notification['accountId']);
if (!$account) { throw new Exception("Account not found", 1); }

$transaction = $notification['tx'];
$current_block_id = $app['xcpd.follower']->getLastProcessedBlock();
$transaction_block_id = $transaction['blockId'];
$number_of_confirmations = $current_block_id - $transaction_block_id + 1;


// re-send the transaction
$notification_manager->sendNotification($account, $transaction, $number_of_confirmations, $current_block_id);


echo "done\n";