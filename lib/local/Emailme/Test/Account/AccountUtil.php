<?php

namespace Emailme\Test\Account;

use EmailMe\Debug\Debug;
use Emailme\Test\Util\RequestUtil;
use Exception;

/*
* AccountUtil
*/
class AccountUtil
{

    ////////////////////////////////////////////////////////////////////////

    public static function newAccountVars() {
        return [
            'email'          => 'account1@devonweller.com',
            'bitcoinAddress' => '1Ni9cJ9jTaDfPHfcr4nSL8DZTpCjuV4Ypa'
        ];

    }

    public static function createNewLifetimeConfirmedAccount($app, $vars=[]) {
        $vars = array_merge(
            [
                'isLifetime' => true,
                'isLifetimeConfirmed' => true,
                'confirmationsToSend' => [0, 3],
            ],
            $vars
        );
        $account = self::createNewAccount($app, $vars);

        // confirm it
        $account = $app['account.manager']->confirmAccount($account);

        return $account;
    }

    public static function createNewAccount($app, $vars=[]) {
        // create the account
        $new_vars = array_merge(AccountUtil::newAccountVars(), $vars);
        $account = $app['account.manager']->newAccount($new_vars);

        // get the most recent account
        return $account;
    }

    public static function createNewAccountThroughWebInterface($app, $vars=[]) {
        $submission_vars = array_merge(AccountUtil::newAccountVars(), $vars);
        $response = RequestUtil::assertResponseWithStatusCode($app, 'POST', '/', $submission_vars, 303);

        // get the most recent account
        $account_dir = $app['directory']('Account');
        return $account_dir->findOne([], ['id' => -1]);
    }

    ////////////////////////////////////////////////////////////////////////

}

