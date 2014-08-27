<?php

namespace Emailme\Models\Directory;

use Utipd\MysqlModel\BaseDocumentMysqlDirectory;
use Exception;

/*
* AccountDirectory
*/
class AccountDirectory extends BaseDocumentMysqlDirectory
{
    protected $column_names = ['emailCanonical', 'bitcoinAddress', 'paymentAddress', 'confirmToken', 'refId', 'createdDate', 'isLifetime', 'isLifetimeConfirmed',];


}
