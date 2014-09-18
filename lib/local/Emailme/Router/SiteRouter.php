<?php

namespace Emailme\Router;

use Exception;
use Emailme\Controller\Exception\WebsiteException;
use Emailme\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
        // redirect to /emailme
        $this->app->match('/', function(Request $request) {
            return new RedirectResponse($this->app->url('home'), 302);
        });



        // mount site
        $emailme_site = $this->app['controllers_factory'];

        // home
        $emailme_site->match('/r/{referralCode}', function(Request $request, $referralCode) {
            return new RedirectResponse($this->app->url('home').'?ref='.$referralCode, 302);
        })->method('GET')->bind('home-referral');

        $emailme_site->match('/', function(Request $request) {
            return $this->app['controller.home']->homeAction($request);
        })->method('GET|POST')->bind('home');

        // about
        $emailme_site->match('/about', function(Request $request) {
            return $this->app['controller.plain']->renderPlainTemplate('about/about.twig');
        })->method('GET')->bind('about');

        // created confirmation
        $emailme_site->match('/account/created', function(Request $request) {
            return $this->app['controller.home']->accountCreatedAction($request);
        })->method('GET')->bind('account-created');

        // email confirmation
        $emailme_site->match('/account/confirm/{token}', function(Request $request, $token) {
            return $this->app['controller.confirm']->confirmAction($request, $token);
        })->method('GET')->bind('account-confirm');

        // account details
        $emailme_site->match('/account/details/{refId}', function(Request $request, $refId) {
            return $this->app['controller.account']->accountDetailsAction($request, $refId);
        })->method('GET')->bind('account-details');

        // account details
        $emailme_site->match('/account/confirmationSetting/{refId}.json', function(Request $request, $refId) {
            return $this->app['controller.account']->accountChangeConfirmationSetting($request, $refId);
        })->method('POST')->bind('account-confirmation-setting');

        // account link
        $emailme_site->match('/account/send-information', function(Request $request) {
            return $this->app['controller.account']->resendAccountLinkAction($request);
        })->method('GET|POST')->bind('send-account-link');

        $emailme_site->match('/account/send-information/finished', function(Request $request) {
            return $this->app['controller.account']->resendAccountLinkActionSuccess($request);
        })->method('GET')->bind('send-account-link-success');


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

        $this->app->mount('/emailme', $emailme_site);

    }

    ////////////////////////////////////////////////////////////////////////

}

