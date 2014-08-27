#!/usr/local/bin/php
<?php 

declare(ticks=1);

use Emailme\Debug\Debug;
use Emailme\Init\Environment;

define('BASE_PATH', realpath(__DIR__.'/../..'));
require BASE_PATH.'/lib/vendor/autoload.php';


// specify the spec as human readable text and run validation and help:
$values = CLIOpts\CLIOpts::run("
  Usage: 
  -i, --account-id <id> account id (required)
  -h, --help show this help
");

$app = Environment::initEnvironment();

$account = $app['directory']('Account')->findById($values['account-id']);
if (!$account) { throw new Exception("Account not found for id: {$values['account-id']}", 1); }

echo "Account ".Debug::desc($account)."\n";
$wif_private_key = $app['bitcoin.addressGenerator']->WIFPrivateKey($account['paymentAddressToken']);

echo $wif_private_key."\n";
