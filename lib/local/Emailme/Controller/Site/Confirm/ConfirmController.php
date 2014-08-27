<?php

namespace Emailme\Controller\Site\Confirm;


use Emailme\Controller\Exception\WebsiteException;
use Emailme\Controller\Site\Base\BaseSiteController;
use Emailme\Debug\Debug;
use Emailme\EventLog\EventLog;
use Exception;
use InvalidArgumentException;
use LinusU\Bitcoin\AddressValidator;
use Respect\Validation\Validator as v;
use Symfony\Component\HttpFoundation\Request;
use Utipd\Form\Exception\FormException;
use Utipd\Form\Sanitizer;
use Utipd\Form\Validator;

/*
* ConfirmController
*/
class ConfirmController extends BaseSiteController
{

    ////////////////////////////////////////////////////////////////////////

    public function __construct($app, $account_manager) {
        $this->account_manager = $account_manager;
        parent::__construct($app);
    }


    public function confirmAction(Request $request, $token) {
        try {
        // lookup the user
            $account = $this->account_manager->findByConfirmToken($token);
            if (!$account) { throw new FormException("This account was not found.  Please check the email and try again.", 1); }

            // confirm and add a refid
            $account = $this->account_manager->confirmAccount($account);
#            Debug::trace("\$account['refId']=".$account['refId'],__FILE__,__LINE__,$this);

            EventLog::logEvent('account.confirm', ['accountId' => $account['id']]);

            // redirect to confirmed successfully
            return $this->renderTwig('account/account-confirm-success.twig', [
                'account' => $account,
            ]);

        } catch (FormException $e) {
            // EventLog::logError('user.purchase.error', ['user' => $user->getID(), 'error' => $e]);
            $error = $e->getDisplayErrorsAsHTML();
        }


        EventLog::logEvent('account.confirm.error', ['token' => $token]);


        return $this->renderTwig('account/account-confirm-error.twig', [
            'error' => isset($error) ? $error : null,
        ]);

    }



    ////////////////////////////////////////////////////////////////////////


}

