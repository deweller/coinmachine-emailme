#!/usr/local/bin/php
<?php 

declare(ticks=1);

use Emailme\Bitcoin\BitcoinKeyUtils;
use Emailme\Currency\CurrencyUtil;
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
    -k, --key <key> WIF encoded private key (required)
    -s, --source <source> Source address (required)
    -d, --destination <destination> Destination address (required)
    -q, --quantity <quantity> Quantity in decimals (required)
    -a, --asset <asset> Token name (required)

    -h, --help show this help
");

$app = Environment::initEnvironment();

$private_key = $values['k'];
$source      = $values['s'];
$destination = $values['d'];
$asset       = $values['a'];
$other_counterparty_vars = [
    'multisig_dust_size'       => CurrencyUtil::numberToSatoshis(0.000025),
    'fee_per_kb'               => CurrencyUtil::numberToSatoshis(0.00001),
    'allow_unconfirmed_inputs' => true,
];

// determine quantity 
$xcpd_client = $app['xcpd.client'];
$assets = $xcpd_client->get_asset_info(['assets' => [$asset]]);
$is_divisible = !!$assets[0]['divisible'];
if ($is_divisible) {
    $quantity = CurrencyUtil::numberToSatoshis($values['q']);
} else {
    // non-divisible assets don't use satoshis
    $quantity = intval(round($values['q']));
}

// get public key
$public_key = BitcoinKeyUtils::publicKeyFromWIF($private_key, $source);

$sender = $app['xcp.sender'];
$transaction_id = $sender->send($public_key, $private_key, $source, $destination, $quantity, $asset, $other_counterparty_vars);

echo "\$transaction_id=\n$transaction_id\n";
echo "\ndone\n";