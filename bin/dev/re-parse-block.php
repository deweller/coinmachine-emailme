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
  -b <block_id> block id (required)
  -h, --help show this help
");

$app = Environment::initEnvironment();
echo "Environment: ".$app['config']['env']."\n";

// route for email URLs
$app['router.site']->route();


$block_id = $values['b'];

echo "forcing re-parse of block $block_id and above\n";


$pdo = $app['mysql.client'];

// native follower
echo "deleting native follower table entries\n";
$sql = "DELETE FROM {$app['mysql.native.databaseName']}.blocks WHERE blockId >= ?";
$sth = $pdo->prepare($sql);
$result = $sth->execute([$block_id]);


// counterparty follower
echo "deleting counterparty follower table entries\n";
$sql = "DELETE FROM {$app['mysql.xcpd.databaseName']}.blocks WHERE blockId >= ?";
$sth = $pdo->prepare($sql);
$result = $sth->execute([$block_id]);


// combined
echo "deleting combined table entries\n";
$sql = "DELETE FROM blockchaintransaction WHERE blockId >= ?";
$pdo = $app['mysql.combined.connectionManager']->getConnection();
$sth = $pdo->prepare($sql);
$result = $sth->execute([$block_id]);
$sql = "DELETE FROM callbacktriggered WHERE blockId >= ?";
$pdo = $app['mysql.combined.connectionManager']->getConnection();
$sth = $pdo->prepare($sql);
$result = $sth->execute([$block_id]);


echo "done\n";