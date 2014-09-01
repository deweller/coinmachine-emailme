<?php

namespace Emailme\Currency;


use Exception;
use Emailme\Debug\Debug;

/*
* CurrencyUtil
*/
class CurrencyUtil
{

    const SATOSHI = 100000000;

    ////////////////////////////////////////////////////////////////////////

    public static function numberToSatoshis($number) {
        return intval(round($number * self::SATOSHI));
    }

    public static function satoshisToNumber($satoshis, $places=null) {
        if (!strlen($satoshis)) { return $satoshis; }
        if ($places === null) { $places = 8; }
        $out = number_format($satoshis / self::SATOSHI, $places);
        if (strpos($out, '.') !== false) {
            $out = rtrim($out, '0');
            $out = rtrim($out, '.');
        }

        return $out;
    }

    public static function addTwigFilters($twig) {
        $filter = new \Twig_SimpleFilter('to_currency', function ($satoshis) {
            return CurrencyUtil::satoshisToNumber($satoshis);
        });
        $twig->addFilter($filter);

        return $twig;
    }


    ////////////////////////////////////////////////////////////////////////

}

