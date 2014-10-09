#!/usr/local/bin/php
<?php 


use BitWasp\BitcoinLib\BIP32;
use BitWasp\BitcoinLib\BitcoinLib;
use Emailme\Auctioneer\Payer\BTCSweeper;
use Utipd\CurrencyLib\CurrencyUtil;
use Emailme\Init\Environment;
use Emailme\Util\Params\ParamsUtil;


define('BASE_PATH', realpath(__DIR__.'/../..'));
require BASE_PATH.'/lib/vendor/autoload.php';

$values = CLIOpts\CLIOpts::run("
  Usage: 
  -i <ref_id> Account ref id (required)
  -h, --help show this help
");

$app = Environment::initEnvironment();
echo "Environment: ".$app['config']['env']."\n";


$account = $app['account.manager']->findByRefId($values['i']);
if (!$account) { throw new Exception("Account not found", 1); }


// publish
$app['notification.manager']->publishNotificationUpdate($account);


echo "done\n";