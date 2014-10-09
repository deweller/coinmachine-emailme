<?php

namespace Emailme\Controller\Site\Admin\Util;

use Exception;
use Utipd\CurrencyLib\CurrencyUtil;
use Emailme\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;

/*
* AdminUtil
*/
class AdminUtil
{

    ////////////////////////////////////////////////////////////////////////

    public static function getFormData($form_spec, Request $request) {
        $data = [];
        foreach($form_spec['fields'] as $entry) {
            if (!isset($entry['function']) OR !$entry['function']) { continue; }

            $name = $entry['name'];
            $value = $request->query->get($name);
#            Debug::trace("request->query=".Debug::desc($request->query->all())."",__FILE__,__LINE__,$this);
#            Debug::trace("name=".Debug::desc($name)." value=".Debug::desc($value)."",__FILE__,__LINE__,$this);
            if ($value === null) {
                if (isset($entry['default']) AND $entry['default']) {
                    $value = $entry['default'];
                }
            }
            $data[$name] = $value;
        }

        return $data;
    }

    public static function findWithFormData($directory, $form_spec, $form_data) {
        $query = [];
        $post_search_filters = [];
        $sort = $form_spec['sort'];
        $limit = null;

        if (isset($form_data['limit']) AND strlen($form_data['limit']) > 0) { $limit = $form_data['limit']; }

        foreach($form_spec['fields'] as $entry) {
            if (!isset($entry['function']) OR !$entry['function']) { continue; }
            $name = $entry['name'];
            $value = isset($form_data[$name]) ? $form_data[$name] : null;
            if ($value === null OR strlen($value) == 0) { continue; }

            // resolve the value
            if (isset($entry['valueResolver'])) {
                $value = call_user_func($entry['valueResolver'], $value, $form_data);
            }

            if ($entry['function'] == 'query') {
                // $use_regex = (isset($entry['regex']) ? $entry['regex'] : false);
                // if ($use_regex) {
                //     $value = new \MongoRegex("/".preg_quote($value)."/i"); 
                // }
                $query[$name] = $value;
            }

            if ($entry['function'] == 'postSearchFilter') {
                $filter = $entry;
                $filter['value'] = $value;
                $post_search_filters[] = $filter;
            }

        }


#        Debug::trace("\$query=".Debug::desc($query)." \$limit=".Debug::desc($limit)."",__FILE__,__LINE__,$this);
#        Debug::trace("\$post_search_filters=\n".json_encode($post_search_filters, 192),__FILE__,__LINE__,$this);
        $post_limit = $limit;
        if ($post_search_filters) { $limit = null; }
        return self::iterateWithFormatting($directory->find($query, $sort, $limit), $post_search_filters, $post_limit);
    }

    public static function defaultFormSpec($overrides=[]) {
        return array_merge([
            'fields' => [
                'intro_spacer' => [
                    'type' => 'spacer',
                    'size' => '8',
                ],
                'limit' => [
                    'type'     => 'text',
                    'function' => 'limit',
                    'name'     => 'limit',
                    'label'    => 'Limit',
                    'size'     => '3',
                    'default'  => 25,
                ],
            ],
            'sort' => ['timestamp' => -1],
        ], $overrides);
    }

    public static function iterateWithFormatting($result_set, $post_search_filters=[], $post_search_limit=null) {
        $count = 0;
        foreach($result_set as $raw_model) {
            if ($post_search_filters) {
                if (!self::resultMatchesFilters($raw_model, $post_search_filters)) { continue; }
            }

#            Debug::trace("\$raw_model=".Debug::desc($raw_model)."",__FILE__,__LINE__,$this);
            $model = self::formatModel($raw_model);

            ++$count;
            yield $model;

            if ($post_search_limit !== null AND $count >= $post_search_limit) { break; }
        }
    }

    public static function formatModel($model, $parent=null) {
        $out = array();

        foreach($model as $k => $v) {
            $is_date_field = false;
            if ($k === 'timestamp') { $is_date_field = true; }
            if (substr($k, -4) === 'Date') { $is_date_field = true; }
            if ($is_date_field AND is_numeric($v)) {
#                Debug::trace("k=$k v=".Debug::desc($v)."",__FILE__,__LINE__,$this);
                $v = date("Y-m-d H:i:s T", $v)." ($v)";
            }

            $is_amount_field = false;
            if ($k === 'amount') { $is_amount_field = true; }
            if (in_array($k, ['prebid','live','late',], true)) { $is_amount_field = true; }
            if ($is_amount_field AND is_numeric($v)) {
                $v = CurrencyUtil::satoshisToNumber($v);
            }

            // recurse
            if (is_array($v) OR is_object($v)) {
                $v = self::formatModel($v, $k);
            }

            // if ($v instanceof \MongoDate) {
            //     $v = date("Y-m-d H:i:s T", $v->sec);
            //     $model[$k] = $v;
            // }
            // if ($v instanceof \MongoId) {
            //     $v = (string)$v;
            //     $model[$k] = $v;
            // }

            $out[$k] = $v;
        }

        if (empty($out)) { $out = new \stdClass(); }
        return $out;
    }

    public static function resultMatchesFilters($model, $post_search_filters) {
        foreach($post_search_filters as $post_search_filter) {
            if (self::resultMatchesFilter($model, $post_search_filter)) { return true; }
        }
        return false;
    }

    public static function resultMatchesFilter($model, $post_search_filter) {
#        Debug::trace("\$model=\n".json_encode($model, 192),__FILE__,__LINE__,$this);
        $model_value = self::resolveModelValue($model, $post_search_filter['name']);
        if ($model_value !== null AND $model_value == $post_search_filter['value']) {
            return true;
        }
        return false;
    }

    public static function resolveModelValue($model, $field_name) {
        $pieces = explode('>', $field_name);
        if (count($pieces) == 1) { return $model[$field_name]; }

        $working_value = $model;
        foreach($pieces as $piece) {
            if (!isset($working_value[$piece])) { return null; }
            $working_value = $working_value[$piece];
        }

        return $working_value;
    }
}

