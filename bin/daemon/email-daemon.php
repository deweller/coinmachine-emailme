#!/usr/local/bin/php
<?php 

declare(ticks=1);

use Emailme\EventLog\EventLog;
use Emailme\Init\Environment;

define('BASE_PATH', realpath(__DIR__.'/../..'));
require BASE_PATH.'/lib/vendor/autoload.php';


// specify the spec as human readable text and run validation and help:
$values = CLIOpts\CLIOpts::run("
  Usage: 
  -e, --environment <environment> specify an environment
  -h, --help show this help
");

$app_env = isset($values['e']) ? $values['e'] : null;

$app = Environment::initEnvironment($app_env, 'emaildaemon');
echo "Environment: ".$app['config']['env']."\n";


EventLog::logEvent('email.daemon.start', []);
try {
    // run the daemon
    $daemon = $app['beanstalk.runnerFactory'](['email']);
    $daemon->run();
} catch (Exception $e) {
    EventLog::logError('email.daemon.error.final', $e);
}
EventLog::logEvent('email.daemon.shutdown', []);
