#!/usr/local/bin/php
<?php 

declare(ticks=1);

use Emailme\Init\Environment;
use Emailme\Util\DB\DBUpdater;
use Emailme\Util\DB\TestDBUpdater;
use Emailme\Util\Params\ParamsUtil;
use Emailme\Util\Twig\TwigUtil;
use BitWasp\BitcoinLib\BitcoinLib;


define('BASE_PATH', realpath(__DIR__.'/../..'));
require BASE_PATH.'/lib/vendor/autoload.php';


// specify the spec as human readable text and run validation and help:
$values = CLIOpts\CLIOpts::run("
  Usage: 
  -p <private_key> WIF private key (required)
  -h, --help show this help
");

$app = Environment::initEnvironment();
$address_version = '00';

$is_valid = BitcoinLib::validate_WIF($values['p']);
if (!$is_valid) { throw new Exception("Invalid WIF", 1); }


$private_key_details = BitcoinLib::WIF_to_private_key($values['p']);
// echo "\$private_key_details:\n".json_encode($private_key_details, 192)."\n";
$private_key = $private_key_details['key'];

$wif = BitcoinLib::private_key_to_WIF($private_key, true, $address_version);
if ($wif !== $values['p']) { throw new Exception("WIF re-encoding failed", 1); }

$compressed_public_key = BitcoinLib::private_key_to_public_key($private_key, true);
$uncompressed_pub_key = BitcoinLib::private_key_to_public_key($private_key, false);
echo "\$uncompressed_pub_key:\n".json_encode($uncompressed_pub_key, 192)."\n";

$address = BitcoinLib::public_key_to_address($compressed_public_key, $address_version);
echo "\$address:\n".json_encode($address, 192)."\n";

echo "\n";
echo "\$compressed_public_key:\n".json_encode($compressed_public_key, 192)."\n";
echo "\n";
