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

    public function isLifetime() {
        return $this->isActive() AND $this['isLifetime'];
    }

}
