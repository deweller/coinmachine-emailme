<?php

namespace Emailme\Controller\Site\Admin;

use Emailme\Controller\Site\Admin\Util\AdminUtil;
use Emailme\Controller\Site\Base\BaseSiteController;
use Emailme\Debug\Debug;
use Exception;
use Symfony\Component\HttpFoundation\Request;

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
        $form_data = AdminUtil::getFormData($form_spec, $request);

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
            $entries[] = [
                'title'    => $account['emailCanonical'],
                'subtitle' => '<span>'.$account['refId']."</span> ".$account['createdDate'],
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
        return $this->app->json($stat_data);
    }

    ////////////////////////////////////////////////////////////////////////

}

