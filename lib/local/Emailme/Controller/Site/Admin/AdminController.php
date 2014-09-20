<?php

namespace Emailme\Controller\Site\Admin;

use Emailme\Controller\Site\Admin\Util\AdminUtil;
use Emailme\Controller\Site\Base\BaseSiteController;
use Emailme\Debug\Debug;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/*
* AdminController
*/
class AdminController extends BaseSiteController
{

    public function __construct($app, $log_entry_directory, $account_directory, $stats_builder) {
        parent::__construct($app);

        $this->log_entry_directory = $log_entry_directory;
        $this->account_directory = $account_directory;
        $this->stats_builder = $stats_builder;
    }



    ////////////////////////////////////////////////////////////////////////

    public function logsAction(Request $request) {
        $form_spec = AdminUtil::defaultFormSpec(['sort' => ['timestamp' => -1, 'id' => -1],]);
        $form_spec['fields'] = [
            'type' => [
                'type'        => 'text',
                'function'    => 'postSearchFilter',
                // 'regex'       => true,
                'name'        => 'type',
                'label'       => 'Type',
                'placeholder' => 'Filter by Type',
                'size'        => '3',
            ],
            'accountId' => [
                'type'          => 'text',
                'function'      => 'postSearchFilter',
                // 'regex'      => true,
                'name'          => 'data>accountId',
                'label'         => 'Account',
                'placeholder'   => '1001',
                'size'          => '3',
                'valueResolver' => function($v) { return $this->resolveAccountId($v); },
            ],
            'intro_spacer' => [
                'type' => 'spacer',
                'size' => '3',
            ],
            'limit' => [
                'type'     => 'text',
                'function' => 'limit',
                'name'     => 'limit',
                'label'    => 'Limit',
                'size'     => '2',
                'default'  => 25,
            ],
            // 's1' => ['type' => 'spacer', 'size' => '4',],
            // 'limit' => $default_form_spec['fields']['limit'],
        ];

        $form_data = AdminUtil::getFormData($form_spec, $request);
#        Debug::trace("\$form_data=\n".json_encode($form_data, 192),__FILE__,__LINE__,$this);

        $entries = [];
        $results = AdminUtil::findWithFormData($this->log_entry_directory, $form_spec, $form_data);
        foreach ($results as $log_entry_model) {
            $entries[] = [
                'title'    => $log_entry_model['type'],
                // 'subtitle' => date("Y-m-d H:i:s T", $log_entry_model['timestamp']),
                'subtitle' => $log_entry_model['timestamp'],
                'data'     => $log_entry_model['data']
            ];
        }
#        Debug::trace("\$entries=".Debug::desc($entries)."",__FILE__,__LINE__,$this);

        return $this->renderTwig('admin/entries/entries.twig', [
            'title'     => 'Logs',
            'form'      => $form_spec,
            'form_data' => $form_data,
            'entries'   => $entries,
        ]);
    }

    public function accountsAction(Request $request) {
        $form_spec = AdminUtil::defaultFormSpec(['sort' => ['createdDate' => -1],]);
        $form_data = AdminUtil::getFormData($form_spec, $request);

        $entries = [];
        $results = AdminUtil::findWithFormData($this->account_directory, $form_spec, $form_data);
        foreach ($results as $account) {
            $account_model = $this->account_directory->create($account);
            $status = $account_model->getAccountStatusDescription();

            $entries[] = [
                'title'    => $account['emailCanonical']." [{$status}]",
                'subtitle' => 
                    ((isset($account['refId']) and strlen($account['refId'])) ?
                        '<a href="'.$this->app->url('account-details', ['refId' => $account['refId']]).'">'.$account['refId']."</a> "
                        : '[unconfirmed] ')
                    .$account['createdDate'],
                'data'     => $account,
            ];
        }
#        Debug::trace("\$entries=".Debug::desc($entries)."",__FILE__,__LINE__,$this);

        return $this->renderTwig('admin/entries/entries.twig', [
            'title'     => 'Accounts',
            'form'      => $form_spec,
            'form_data' => $form_data,
            'entries'   => $entries,
        ]);
    }

    public function statsAction(Request $request, $stat) {
        $stat_data = $this->stats_builder->buildStat($stat);
        if (strlen($callback = $request->query->get('callback'))) {
            $response = new JsonResponse($stat_data);
            $response->setCallback($callback);
            return $response;
        }
        return $this->app->json($stat_data);
    }

    ////////////////////////////////////////////////////////////////////////

    protected function resolveAccountId($raw_value) {
        $account = null;
        if (is_numeric($raw_value)) {
            $account = $this->account_directory->findById($raw_value);
        }

        if (!$account) {
            // try by bitcoin address
            $account = $this->account_directory->findOne(['bitcoinAddress' => $raw_value]);
        }
        if (!$account) {
            // try refId
            $account = $this->account_directory->findOne(['refId' => $raw_value]);
        }

        if ($account) { return $account['id']; }
        return 'notfound';
    }

}

