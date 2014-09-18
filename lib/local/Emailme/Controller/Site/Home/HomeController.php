<?php

namespace Emailme\Controller\Site\Home;

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
* HomeController
*/
class HomeController extends BaseSiteController
{

    ////////////////////////////////////////////////////////////////////////

    public function __construct($app, $account_manager) {
        $this->account_manager = $account_manager;
        parent::__construct($app);
    }

    public function homeAction(Request $request) {
        $submitted_data = null;
        if ($request->isMethod('POST')) {
            $submitted_data = $request->request->all();
#            Debug::trace("\$submitted_data=".json_encode($submitted_data, 192),__FILE__,__LINE__,$this);
            try {
                // validate the data
                $validator = new Validator($this->buildSignupFormSpec());
                $sanitized_data = $validator->sanitizeSubmittedData($submitted_data);
                $sanitized_data = $validator->validateSubmittedData($sanitized_data);

                // create a account
                $new_account_vars = $sanitized_data;

                // new account

                //  check for existing account
                $existing_account = $this->account_manager->findByBitcoinAddressAndEmail($new_account_vars['bitcoinAddress'], $new_account_vars['email']);
                if ($existing_account) {
                    if ($existing_account->isActive()) {
                        // already confirmed
                        $url = $this->app->url('send-account-link', $new_account_vars);
                        throw new FormException("This account is already active.  <a href=\"{$url}\">Resend link</a>?");
                    } else {
                        // not confirmed - just pretend like it doesn't exist yet
                        $account = $existing_account;
                    }

                    EventLog::logEvent('account.created.existing', ['accountId' => $account['id'], 
                                'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null, 
                                'proxyIp' => isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : null]
                            );
                } else {
                    $account = $this->account_manager->newAccount($new_account_vars);

                    EventLog::logEvent('account.created', ['accountId' => $account['id'], 
                                'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null, 
                                'proxyIp' => isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : null]
                            );

                }

                // send email (in background)
#                Debug::trace("\$account=".json_encode($account, 192),__FILE__,__LINE__,$this);
                EventLog::logEvent('email.confirmation', ['accountId' => $account['id'],]);
                $account = $this->account_manager->sendConfirmationEmail($account);

                // go to the created confirmation
                return $this->app->redirect($this->app->url('account-created', []), 303);

            } catch (InvalidArgumentException $e) {
                $error = $e->getMessage();
            } catch (FormException $e) {
                $error = $e->getDisplayErrorsAsHTML();
            }
        } else {
            $validator = new Validator($this->buildSignupFormSpec());
        }

        return $this->renderTwig('home/home.twig', [
            'submittedData' => isset($submitted_data) ? $submitted_data : array_merge($validator->getDefaultValues(), ['referredBy' => $request->query->get('ref')]),
            'error'         => isset($error) ? $error : null,
        ]);

    }

    public function accountCreatedAction(Request $request) {
        return $this->renderTwig('account/account-created.twig', [
        ]);
    }


    ////////////////////////////////////////////////////////////////////////


    protected function buildSignupFormSpec() {
        $spec = [
            'email' => [
                'name'      => 'email',
                'label'     => 'Email',
                'default'   => '',
                'validation' => v::email(),
                'sanitizer' => Sanitizer::trim(),
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
            'referredBy' => [
                'name'       => 'referredBy',
                'label'      => 'Referred By',
                'default'   => '',
                // 'validation' => v::oneOf(v::string()->length(10), v::equals('')),
                'sanitizer'  => Sanitizer::trim(),
                'error'      => 'Invalid Referral Code.',
            ],
        ];
        return $spec;
    }

}

