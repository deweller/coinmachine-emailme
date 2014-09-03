<?php

namespace Emailme\Managers\Balance;

use Emailme\Currency\CurrencyUtil;
use Emailme\Debug\Debug;
use Exception;

/*
* AssetBalanceBuilder
*/
class AssetBalanceBuilder
{

    ////////////////////////////////////////////////////////////////////////

    public function __construct($xcpd_client, $guzzle) {
        $this->xcpd_client = $xcpd_client;
        $this->guzzle = $guzzle;
    }

    public function getAssetBalance($address, $asset) {
        if ($asset == 'BTC') {
            $quantity = $this->getUnspentOutputsTotal($address);
            $quantity = CurrencyUtil::numberToSatoshis($quantity);
        } else {
            // XCP
            $quantity = 0;
            $balances = $this->xcpd_client->get_balances(['filters' => ['field' => 'address', 'op' => '==', 'value' => $address]]);
            foreach($balances as $balance) {
                if ($balance['asset'] == $asset) {
                    $quantity = $balance['quantity'];
                }
            }

            if ($quantity > 0) {
                $assets = $this->xcpd_client->get_asset_info(['assets' => [$asset]]);
                $is_divisible = $assets[0]['divisible'];

                if (!$is_divisible) {
                    $quantity = CurrencyUtil::numberToSatoshis($quantity);
                }
            }
        }

        return $quantity;
    }

    public function getUnspentOutputsTotal($address) {
        return $this->sumUnspentOutputs($this->getUnspentOutputs($address));
    }

    public function getUnspentOutputs($address) {
        // get all funds (use blockr)
        // http://btc.blockr.io/api/v1/address/unspent/1EuJjmRA2kMFRhjAee8G6aqCoFpFnNTJh4
        $response = $this->guzzle->get('http://btc.blockr.io/api/v1/address/unspent/'.$address);
        $json_data = $response->json();
        return $json_data['data']['unspent'];
    }

    ////////////////////////////////////////////////////////////////////////

    protected function sumUnspentOutputs($unspent_outputs) {
        $float_total = 0;
        foreach($unspent_outputs as $unspent_output) {
            $float_total += $unspent_output['amount'];

        }
        return $float_total;
    }

}

