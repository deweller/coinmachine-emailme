// <?php

// namespace Emailme\Managers;

// use EmailMe\Debug\Debug;
// use Exception;

// /*
// * PaymentAddressManager
// */
// class PaymentAddressManager
// {

//     ////////////////////////////////////////////////////////////////////////

//     public function __construct($mysql_db, $bitcoin_address_generator) {
//         $this->mysql_db = $mysql_db;
//         $this->bitcoin_address_generator = $bitcoin_address_generator;
//     }

//     public function newPaymentAddressInfo($token) {
//         // get the next offset
//         $offset = $this->nextOffset();
//         $address = $this->bitcoin_address_generator->publicAddress($token);
//     }

//     public function nextOffset() {
//         // get the next offset
//         $this->mysql_db->exec("UPDATE paymentaddressoffset SET seq = LAST_INSERT_ID(seq+1)");
//         $sth = $this->mysql_db->query("SELECT LAST_INSERT_ID() AS lid");
//         $row = $sth->fetch(\PDO::FETCH_ASSOC);
//         if ($row['lid'] == 0) {
//             // no row yet - insert the first one
//             $this->mysql_db->exec("INSERT INTO paymentaddressoffset VALUES(0);");
//             $this->mysql_db->exec("UPDATE paymentaddressoffset SET seq = LAST_INSERT_ID(seq+1)");

//             // now get the seq
//             $sth = $this->mysql_db->query("SELECT LAST_INSERT_ID() AS lid");
//             $row = $sth->fetch(\PDO::FETCH_ASSOC);
//         }

//         return $row['lid'];
//     }


//     ////////////////////////////////////////////////////////////////////////

// }

