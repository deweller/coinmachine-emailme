#!/usr/local/bin/php
<?php 


use BitWasp\BitcoinLib\BIP32;
use BitWasp\BitcoinLib\BitcoinLib;
use Emailme\Auctioneer\Payer\BTCSweeper;
use Emailme\Currency\CurrencyUtil;
use Emailme\Init\Environment;


define('BASE_PATH', realpath(__DIR__.'/../..'));
require BASE_PATH.'/lib/vendor/autoload.php';


$app_env = isset($values['e']) ? $values['e'] : null;
$app = Environment::initEnvironment($app_env);
echo "Environment: ".$app['config']['env']."\n";

$context =  $app['request_context'];
$context->setHost($app['config']['site']['host']);
$context->setHttpPort($app['config']['site']['httpPort']);
$app['router.site']->route();

print $app->url('account-details', ['refId' => 'foo-ref-id'], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL)."\n";




