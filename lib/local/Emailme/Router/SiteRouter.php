<?php

namespace Emailme\Router;

use Exception;
use Emailme\Controller\Exception\WebsiteException;
use Emailme\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;

/*
* SiteRouter
*/
class SiteRouter
{

    ////////////////////////////////////////////////////////////////////////

    public function __construct($app) {
        $this->app = $app;
    }

    public function route() {
        // home
        $this->app->match('/', function(Request $request) {
            // return $this->app['twig']->render('home/home.twig', ['error' => $error]);
            return $this->app['controller.home']->homeAction($request);
        })->method('GET|POST')->bind('home');

        // created confirmation
        $this->app->match('/account/created', function(Request $request) {
            return $this->app['controller.home']->accountCreatedAction($request);
        })->method('GET')->bind('account-created');

        // email confirmation
        $this->app->match('/account/confirm/{token}', function(Request $request, $token) {
            return $this->app['controller.confirm']->confirmAction($request, $token);
        })->method('GET')->bind('account-confirm');

        // account details
        $this->app->match('/account/details/{refId}', function(Request $request, $refId) {
            return $this->app['controller.account']->accountDetailsAction($request, $refId);
        })->method('GET')->bind('account-details');

        // account details
        $this->app->match('/account/confirmationSetting/{refId}.json', function(Request $request, $refId) {
            return $this->app['controller.account']->accountChangeConfirmationSetting($request, $refId);
        })->method('POST')->bind('account-confirmation-setting');


        // account link
        $this->app->match('/account/send-information', function(Request $request) {
            return $this->app['controller.account']->resendAccountLinkAction($request);
        })->method('GET|POST')->bind('send-account-link');

        $this->app->match('/account/send-information/finished', function(Request $request) {
            return $this->app['controller.account']->resendAccountLinkActionSuccess($request);
        })->method('GET')->bind('send-account-link-success');


        // $this->app->match('/create/auction/{auctionRefId}', function(Request $request, $auctionRefId) {
        //     return $this->app['controller.auction.admin']->confirmAuctionAction($request, $auctionRefId);
        // })->method('GET|POST')->bind('create-auction-confirm');


        // $this->app->match('/auction/{slug}', function(Request $request, $slug) {
        //     return $this->app['controller.auction.public']->viewAuctionAction($request, $slug);
        // })->method('GET|POST')->bind('public-auction');



        // default error handler
        $this->app->error(function (Exception $e, $code) {
            // use debug mode
            if ($this->app['debug']) { return; }

            $error = null;
            if ($e instanceof WebsiteException) {
                $error = $e->getDisplayErrorsAsHTML();
            }

            return $this->app['twig']->render('error/error.twig', ['error' => $error]);
        });

    }

    ////////////////////////////////////////////////////////////////////////

}

