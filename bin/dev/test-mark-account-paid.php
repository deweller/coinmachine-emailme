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
  -p <params> update vars
  -h, --help show this help
");

$app = Environment::initEnvironment();
echo "Environment: ".$app['config']['env']."\n";


$account = $app['account.manager']->findByRefId($values['i']);
if (!$account) { throw new Exception("Account not found", 1); }

$update_vars = [];
$update_vars['isLifetime'] =  true;
$update_vars['isLifetimeConfirmed'] =  true;

// update 
echo "\$update_vars:\n".json_encode($update_vars, 192)."\n";
$account = $app['account.manager']->update($account, $update_vars);


// mark paid
echo "\$update_vars:\n".json_encode($update_vars, 192)."\n";
$app['referral.manager']->handleNewPaidAccount($account);


// publish
$account = $account->reload();
$app['payment.manager']->publishPaymentUpdate($account);

// also publish to referrer account
if (strlen($account['referredBy']) AND $referrer_account = $app['account.manager']->findByReferralCode($account['referredBy'])) {
    $app['payment.manager']->publishPaymentUpdate($referrer_account);
}


echo "done\n";
