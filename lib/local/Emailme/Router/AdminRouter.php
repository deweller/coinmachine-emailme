<?php

namespace Emailme\Router;

use Exception;
use Emailme\Controller\Exception\WebsiteException;
use Emailme\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/*
* AdminRouter
*/
class AdminRouter
{

    ////////////////////////////////////////////////////////////////////////

    public function __construct($app) {
        $this->app = $app;
    }


    public function route() {

        $http_auth = function() {
            if (!isset($_SERVER['PHP_AUTH_USER']))
            {
                // return $app->json(array('Message' => 'Not Authorized'), 401);
                return new Response('Not Authorized', 401, array('WWW-Authenticate' => 'Basic realm="'.$this->app['config']['admin.realm'].'"'));
            }
            else
            {
                //once the user has provided some details, check them
                $users = array(
                    $this->app['config']['admin.login'] => $this->app['config']['admin.password'],
                );

                if($users[$_SERVER['PHP_AUTH_USER']] !== $_SERVER['PHP_AUTH_PW']) {
                    return new Response('Not Authorized', 401, array('WWW-Authenticate' => 'Basic realm="'.$this->app['config']['admin.realm'].'"'));
                }

            }
        };

        $this->app->match('/admin/logs', function(Request $request) {
            return $this->app['controller.admin']->logsAction($request);
        })->method('GET|POST')->before($http_auth);

        $this->app->match('/admin/accounts', function(Request $request) {
            return $this->app['controller.admin']->accountsAction($request);
        })->method('GET|POST')->before($http_auth);

        $this->app->match('/flip2369258/stats/{stat}', function(Request $request, $stat) {
            return $this->app['controller.admin']->statsAction($request, $stat);
        })->method('GET')->before($http_auth);

    }

    ////////////////////////////////////////////////////////////////////////



}

