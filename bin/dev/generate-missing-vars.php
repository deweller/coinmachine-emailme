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
  -h, --help show this help
");

$app = Environment::initEnvironment();
echo "Environment: ".$app['config']['env']."\n";

$account_dir = $app['directory']('Account');
$token_generator = $app['token.generator'];

foreach ($account_dir->findAll() as $account) {
    $update_vars = [];

    if (!strlen($account['referralCode'])) { $update_vars['referralCode'] = $token_generator->generateToken('REFERRAL', 9); }
    if (!strlen($account['referralEarnings'])) { $update_vars['referralEarnings'] = 0; }
    if (!strlen($account['referralCount'])) { $update_vars['referralCount'] = 0; }

    // update 
    if ($update_vars) {
        echo "\$update_vars:\n".json_encode($update_vars, 192)."\n";
        $account = $app['account.manager']->update($account, $update_vars);
    }
}

echo "done\n";
