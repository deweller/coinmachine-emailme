#!/usr/local/bin/php
<?php 

declare(ticks=1);

use Emailme\Init\Environment;
use Emailme\Util\DB\DBUpdater;
use Emailme\Util\DB\TestDBUpdater;
use Emailme\Util\Params\ParamsUtil;
use Emailme\Util\Twig\TwigUtil;

define('BASE_PATH', realpath(__DIR__.'/../..'));
require BASE_PATH.'/lib/vendor/autoload.php';


// specify the spec as human readable text and run validation and help:
$values = CLIOpts\CLIOpts::run("
  Usage: 
  -c <command> [get_running_info] (required)
  -p <params> yaml encoded params
  -w unlock wallet first
  -h, --help show this help
");

$app = Environment::initEnvironment();

if (isset($values['p'])) {
    $params = ParamsUtil::interpretJSONOrYaml($values['p']);
    echo "Using parameters: ".json_encode($params, 192)."\n";
} else {
    $params = [];
}
echo "\$params:\n".json_encode($params, 192)."\n";
echo "using connection string: {$app['xcpd.connectionString']}\n";

// unlock wallet if needed
if (isset($values['w'])) {
    $wallet_passphrase = $app['config']['bitcoin.passphrase'];
    $result = $app['native.client']->walletpassphrase($wallet_passphrase, 60);
    echo "Wallet unlocked\n";
}

// call the command
$xcp_client = $app['xcpd.client'];
$result = $xcp_client->__call($values['c'], [$params]);
echo json_encode($result, 192)."\n";

