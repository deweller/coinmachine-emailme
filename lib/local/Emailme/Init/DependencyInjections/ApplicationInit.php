<?php

namespace Emailme\Init\DependencyInjections;

use Exception;
use Emailme\Currency\CurrencyUtil;
use Emailme\Debug\Debug;
use Emailme\Init\Controller\ControllerResolver;
use Symfony\Component\HttpFoundation\Response;

/*
* ApplicationInit
*/
class ApplicationInit {

    public static function init($app) {
        self::initApplication($app);
    }

    public static function initApplication($app) {
        // config
        $app['config'] = $app->share(function($app) {
            $debug = $app['env'] == 'prod' ? false : true;
            $loader = new \Utipd\Config\ConfigLoader(BASE_PATH.'/etc/app-config', BASE_PATH.'/var/cache/app-config', $debug);
            return $loader->loadYamlFile($app['env'].'.yml');
        });


        // debug setting
        $app['debug'] = function($app) { return $app['config']['app.debug']; };


        // monolog
        $app->register(new \Silex\Provider\MonologServiceProvider(), [
            'monolog.logfile' => BASE_PATH.'/var/log/trace.log',
            'monolog.name'    => 'ltba',
            'monolog.level'   => \Monolog\Logger::DEBUG,
        ]);
        $app['monolog.handler'] = function () use ($app) {
            $level = \Silex\Provider\MonologServiceProvider::translateLevel($app['monolog.level']);
            $stream = new \Monolog\Handler\StreamHandler($app['monolog.logfile'], $level);
            $stream->setFormatter(new \Monolog\Formatter\LineFormatter("[%datetime%] %channel%.%level_name% %message%\n", "Y-m-d H:i:s", true));
            return $stream;
        };

        // routing
        $app['router.site'] = function($app) {
            return new \Emailme\Router\SiteRouter($app);
        };
        $app['router.admin'] = function($app) {
            return new \Emailme\Router\AdminRouter($app);
        };

        // twig
        $app->register(new \Silex\Provider\TwigServiceProvider(), array(
            'twig.path' => BASE_PATH.'/twig',
        ));
        $app['twig.path'] = BASE_PATH.'/twig/html';
        $app["twig"] = $app->share($app->extend("twig", function (\Twig_Environment $twig, \Silex\Application $app) {
            return CurrencyUtil::addTwigFilters($twig);
        }));

        // url and domain for CLI apps
        $context =  $app['request_context'];
        $context->setHost($app['config']['site']['host']);
        $context->setHttpPort($app['config']['site']['httpPort']);
        $context->setHttpsPort($app['config']['site']['httpsPort']);


        $app->register(new \Silex\Provider\UrlGeneratorServiceProvider());

    }






}

