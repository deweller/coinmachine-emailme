<?php

namespace Emailme\Controller\Site\Account;

use Emailme\Controller\Exception\WebsiteException;
use Emailme\Controller\Site\Base\BaseSiteController;
use Emailme\Debug\Debug;
use Emailme\EventLog\EventLog;
use Exception;
use InvalidArgumentException;
use LinusU\Bitcoin\AddressValidator;
use Symfony\Component\HttpFoundation\Request;
use Utipd\Form\Exception\FormException;
use Utipd\Form\Sanitizer;
use Utipd\Form\Validator;
use Respect\Validation\Validator as v;


/*
* AccountController
*/
class AccountController extends BaseSiteController
{

    ////////////////////////////////////////////////////////////////////////

    public function __construct($app, $account_manager, $payment_manager, $notifications_manager) {
        $this->account_manager = $account_manager;
        $this->notifications_manager = $notifications_manager;
        $this->payment_manager = $payment_manager;
        parent::__construct($app);
    }


    public function accountDetailsAction(Request $request, $ref_id) {
        try {
            // lookup the user
            $account = $this->account_manager->findByRefId($ref_id);
            if (!$account) { throw new FormException("This account was not found"); }

            // redirect to confirmed successfully
            return $this->renderTwig('account/account-details.twig', [
                'account'       => $account,
                'notifications' => iterator_to_array($this->notifications_manager->allNotifications($account)),
            ]);

        } catch (FormException $e) {
            // EventLog::logError('user.purchase.error', ['user' => $user->getID(), 'error' => $e]);
            $error = $e->getDisplayErrorsAsHTML();
        }


        return $this->renderTwig('account/account-details-error.twig', [
            'error' => isset($error) ? $error : null,
        ]);

    }

    public function resendAccountLinkAction(Request $request) {
        $submitted_data = null;
        if ($request->isMethod('POST')) {
            $submitted_data = $request->request->all();
            try {
                // validate the data
                $validator = new Validator($this->buildSignupFormSpec());
                $sanitized_data = $validator->sanitizeSubmittedData($submitted_data);
                $sanitized_data = $validator->validateSubmittedData($sanitized_data);

                //  check for existing account
                $account = $this->account_manager->findByBitcoinAddressAndEmail($sanitized_data['bitcoinAddress'], $sanitized_data['email']);
                if (!$account) { throw new FormException("Sorry.  We couldn't send you anything because this account was not found.", 1); }

                // send the email
#                Debug::trace("\$account=\n".json_encode($account, 192),__FILE__,__LINE__,$this);
                $this->account_manager->sendAccountDetailsLink($account);
                EventLog::logEvent('account.link.email', ['accountId' => $account['id'], 
                        'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null, 
                        'proxyIp' => isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : null]
                    );

                // go to the sent confirmation
                return $this->app->redirect($this->app->url('send-account-link-success', []), 303);


            } catch (InvalidArgumentException $e) {
                $error = $e->getMessage();
            } catch (FormException $e) {
                $error = $e->getDisplayErrorsAsHTML();
            }
        } else {
            $validator = new Validator($this->buildSignupFormSpec());
        }

        return $this->renderTwig('account/send-account-link.twig', [
            'submittedData' => isset($submitted_data) ? $submitted_data : array_merge($validator->getDefaultValues(), $request->query->all()),
            'error'         => isset($error) ? $error : null,
        ]);
    }

    public function resendAccountLinkActionSuccess(Request $request) {
        return $this->renderTwig('account/send-account-link-success.twig', [
        ]);
    } 

    public function accountChangeConfirmationSetting(Request $request, $ref_id) {
        try {
            // lookup the user
            $account = $this->account_manager->findByRefId($ref_id);
            if (!$account) { throw new FormException("This account was not found"); }

            // verify account status
            if (!$account->isLifetime()) { throw new FormException("This feature is available with paid accounts only."); }


            // update 
            $submitted_data = json_decode($request->getContent(), true);
#            Debug::trace("\$submitted_data=\n".json_encode($submitted_data, 192),__FILE__,__LINE__,$this);

            $confirmations_map = array_fill_keys($account['confirmationsToSend'], true);
            $number = intval($submitted_data['confirmationsNumber']);
            if (!in_array($number, [0,1,3,6])) { throw new Exception("Invalid data received", 1); }
            if ($submitted_data['confirmationValue']) {
                $confirmations_map[$number] = true;
                ksort($confirmations_map);
            } else {
                unset($confirmations_map[$number]);
            }
#            Debug::trace("\$submitted_data=\n".json_encode($submitted_data, 192),__FILE__,__LINE__,$this);
#            Debug::trace("\$confirmations_map=\n".json_encode($confirmations_map, 192),__FILE__,__LINE__,$this);
            $update_vars['confirmationsToSend'] = array_keys($confirmations_map);
#            Debug::trace("\$update_vars=\n".json_encode($update_vars, 192),__FILE__,__LINE__,$this);


            $account = $this->account_manager->update($account, $update_vars);
            EventLog::logEvent('account.confirmChange', ['accountId' => $account['id'], 'number' => $account['confirmationsToSend'], 'value' => $account['confirmationValue']]);

            // publish
            $this->payment_manager->publishPaymentUpdate($account);

            $out = [
                'success' => true,
            ];

        } catch (FormException $e) {
            EventLog::logError('account.confirmChange.error', ['accountId' => $account['id'], 'error' => $e]);

            // EventLog::logError('user.purchase.error', ['user' => $user->getID(), 'error' => $e]);
            $errors = $e->getDisplayErrorsAsArray();
            $out = [
                'success' => false,
                'errors' => $errors,
            ];
        }

        return $this->app->json($out);
    }



    ////////////////////////////////////////////////////////////////////////

    protected function buildSignupFormSpec() {
        $spec = [
            'email' => [
                'name'      => 'email',
                'label'     => 'Email',
                'default'   => '',
                'validation' => v::email(),
                'sanitizer' => function($v) { return trim($v); },
                'error'     => 'Please enter a valid email address.',
            ],
            'bitcoinAddress' => [
                'name'       => 'bitcoinAddress',
                'label'      => 'Bitcoin Address',
                'default'   => '',
                'validation' => function($value) { return strlen($value) ? AddressValidator::isValid($value) : false; },
                'sanitizer'  => Sanitizer::trim(),
                'error'      => 'Address must be a valid Bitcoin address.',
            ],
        ];
        return $spec;
    }


}

