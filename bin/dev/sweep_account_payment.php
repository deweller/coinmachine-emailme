#!/usr/local/bin/php
<?php 

declare(ticks=1);

use Utipd\CurrencyLib\CurrencyUtil;
use Emailme\Init\Environment;
use Emailme\Util\DB\DBUpdater;
use Emailme\Util\DB\TestDBUpdater;
use Emailme\Util\Params\ParamsUtil;
use Emailme\Util\Twig\TwigUtil;
use Utipd\BitcoinAddressLib\BitcoinKeyUtils;

define('BASE_PATH', realpath(__DIR__.'/../..'));
require BASE_PATH.'/lib/vendor/autoload.php';


// specify the spec as human readable text and run validation and help:
$values = CLIOpts\CLIOpts::run("
    Usage: 
    -i, --account-id <id> account id (required)
    -d, --destination <destination> Destination address (defaults to platform address)

    -h, --help show this help
");

$app = Environment::initEnvironment();

$account = $app['directory']('Account')->findById($values['i']);
if (!$account) { throw new Exception("Account not found for id: {$values['i']}", 1); }

$source      = $account['paymentAddress'];
$private_key = $app['bitcoin.addressGenerator']->WIFPrivateKey($account['paymentAddressToken']);
$destination = isset($values['d']) ? $values['d'] : $app['config']['platform.incomeAddress'];

// get balance
$xcpd_client = $app['xcpd.client'];
$asset       = 'LTBCOIN';
$balances = $xcpd_client->get_balances(['filters' => ['field' => 'address', 'op' => '==', 'value' => $source]]);
// echo "\$balances:\n".json_encode($balances, 192)."\n";
$ltbcoin_quantity = 0;
foreach($balances as $balance) {
    if ($balance['asset'] == 'LTBCOIN') {
        $ltbcoin_quantity = $balance['quantity'];
    }
}

// sweep LTBcoin
if ($ltbcoin_quantity) {
    // sweeping LTBCOIN
    echo "Sweeping ".CurrencyUtil::satoshisToNumber($ltbcoin_quantity)." LTBCOIN\n";

    $other_counterparty_vars = [
        'multisig_dust_size'       => CurrencyUtil::numberToSatoshis(0.000025),
        'fee_per_kb'               => CurrencyUtil::numberToSatoshis(0.00001),
        'allow_unconfirmed_inputs' => true,
    ];


    // get public key
    echo "\$private_key=$private_key\n";
    echo "\$source=$source\n";

    $public_key = BitcoinKeyUtils::publicKeyFromWIF($private_key, $source);

    $sender = $app['xcp.sender'];

    // echo "\sending:\n".json_encode([$public_key, $private_key, $source, $destination, $ltbcoin_quantity, $asset, $other_counterparty_vars], 192)."\n";

    $transaction_id = $sender->send($public_key, $private_key, $source, $destination, $ltbcoin_quantity, $asset, $other_counterparty_vars);
    echo "\$transaction_id=\n$transaction_id\n";
} else {
    echo "No LTBCOIN found\n";

    // sweep BTC
    echo "Sweeping BTC";
    $payer = $app['bitcoin.payer'];
    list($transaction_id, $float_balance_sent) = $payer->sweepBTC($source, $destination, $private_key, 0.00001);
    echo "Sent $float_balance_sent to $destination\n";
    echo "Transaction ID: $transaction_id\n";

}



echo "\ndone\n";
