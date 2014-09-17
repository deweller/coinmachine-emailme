<?php

namespace Emailme\Controller\Site\Admin\Stats;

use EmailMe\Debug\Debug;
use Emailme\Currency\CurrencyUtil;
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
            'paid' => $this->account_directory->findCount(['isLifetime' => true, 'isComp' => false]),
            'comp' => $this->account_directory->findCount(['isComp' => true]),
        ];
        return $out;
    }

    protected function buildStat_revenue() {
        $other_total = 0;
        $balance_totals = ['BTC' => 0, 'LTBCOIN' => 0];
        foreach ($this->account_directory->find(['isLifetime' => true]) as $account) {
            foreach ($account['balance'] as $token => $amount) {
                if (!isset($balance_totals[$token])) { $balance_totals[$token] = 0; }
                $balance_totals[$token] += $amount;

                if ($token != 'BTC' AND $token != 'LTBCOIN') { $other_total += $amount; }
            }
        }

        $out = [
            'BTC'     => CurrencyUtil::satoshisToNumber($balance_totals['BTC'], 4),
            'LTBCOIN' => CurrencyUtil::satoshisToNumber($balance_totals['LTBCOIN']),
            'OTHER'   => CurrencyUtil::satoshisToNumber($other_total),
        ];
        return $out;
    }
}

