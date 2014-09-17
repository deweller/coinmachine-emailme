<?php

namespace Emailme\Models\Model;

use Utipd\MysqlModel\BaseDocumentMysqlModel;
use Exception;

/*
* AccountModel
*/
class AccountModel extends BaseDocumentMysqlModel
{

    public function isActive() {
        if (!$this['isConfirmed']) { return false; }
        if ($this['isLifetime']) { return true; }
        if ($this['notificationsRemaining'] > 0) { return true; }
        return false;
    }

    public function getAccountStatusDescription() {
        switch (true) {
            case !$this['isConfirmed']:
                return 'unconfirmed';
            case $this['isComp']:
                return 'comp';
            case $this['isLifetimeConfirmed']:
                return 'lifetime';
            case $this['isLifetime']:
                return 'lifetime (pending)';
            case $this['isConfirmed']:
                $is_active = ($this['notificationsRemaining'] > 0);
                return 'confirmed - '.($is_active?'active':'inactive');
        }
        return 'unknown';
    }

    public function isLifetime() {
        return $this->isActive() AND $this['isLifetime'];
    }

}
