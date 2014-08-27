<?php

namespace Emailme\Controller\Site\Admin\Stats;

use EmailMe\Debug\Debug;
use Exception;

/*
* StatsBuilder
*/
class StatsBuilder
{

    ////////////////////////////////////////////////////////////////////////

    public function __construct($log_entry_directory, $account_directory) {
        $this->log_entry_directory = $log_entry_directory;
        $this->account_directory = $account_directory;
    }

    public function buildStat($stat) {
        return call_user_func([$this, "buildStat_{$stat}"]);
    }

    ////////////////////////////////////////////////////////////////////////

    protected function buildStat_accounts() {
        $out = [
            'freeUnconfirmed' => $this->account_directory->findCountRaw("SELECT * FROM account WHERE isLifetime = false AND paymentAddress IS NULL", []),
            'freeConfirmed' => $this->account_directory->findCountRaw("SELECT * FROM account WHERE isLifetime = false AND paymentAddress IS NOT NULL", []),
            'paid' => $this->account_directory->findCount(['isLifetime' => true]),
        ];
        return $out;
    }
}

