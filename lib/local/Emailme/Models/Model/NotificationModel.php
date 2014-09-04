<?php

namespace Emailme\Models\Model;

use Utipd\MysqlModel\BaseDocumentMysqlModel;
use Exception;

/*
* NotificationModel
*/
class NotificationModel extends BaseDocumentMysqlModel
{

    public function isBlockchainTransactionId() {
        return (substr($this['tx_hash'], 0, 1) === 'M' ? false : true);
    }

}
