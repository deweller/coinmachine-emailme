<?php

namespace Emailme\Models\Directory;

use Utipd\MysqlModel\BaseDocumentMysqlDirectory;
use Exception;

/*
* EventLogDirectory
*/
class EventLogDirectory extends BaseDocumentMysqlDirectory
{
    protected $column_names = ['timestamp',];

}
