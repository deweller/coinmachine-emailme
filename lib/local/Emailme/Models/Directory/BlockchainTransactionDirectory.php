<?php

namespace Emailme\Models\Directory;

use Utipd\MysqlModel\BaseDocumentMysqlDirectory;
use Exception;

/*
* BlockchainTransactionDirectory
*/
class BlockchainTransactionDirectory extends BaseDocumentMysqlDirectory
{

    protected $column_names = ['accountId','blockId','tx_hash','isMempool','isNative',];

    public function findByAccountId($account_id, $sort=null) {
        if ($sort === null) { $sort = ['isMempool' => 1, 'blockId' => 1, 'id' => 1]; }
        return $this->find(['accountId' => $account_id], $sort);
    }


}
