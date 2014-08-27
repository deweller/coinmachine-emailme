<?php

namespace Emailme\Models\Directory;

use Utipd\MysqlModel\BaseDocumentMysqlDirectory;
use Exception;

/*
* NotificationDirectory
*/
class NotificationDirectory extends BaseDocumentMysqlDirectory
{

    protected $column_names = ['accountId','tx_hash','isNative','confirmations','sentDate',];

}
