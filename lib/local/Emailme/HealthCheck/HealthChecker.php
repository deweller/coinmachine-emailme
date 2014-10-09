<?php

namespace Emailme\HealthCheck;

use EmailMe\Debug\Debug;
use Exception;

/*
* HealthChecker
*/
class HealthChecker
{

    ////////////////////////////////////////////////////////////////////////

    public function __construct($xcpd_follower, $native_follower, $heartbeat) {
        $this->xcpd_follower   = $xcpd_follower;
        $this->native_follower = $native_follower;
        $this->heartbeat       = $heartbeat;
    }

    public function checkHealth() {
        $processs = ['xcpd','native','blockchaindaemon','emaildaemon'];

        $out = [];
        foreach($processs as $process) {
            $is_alive   = false;
            $error      = null;
            $error_code = 0;

            try {
                $method_name = "checkHealth_{$process}";
                if (!method_exists($this, $method_name)) { throw new Exception("Method not found: $method_name", 1); }
                call_user_func([$this, $method_name]);
                $is_alive = true;

            } catch (Exception $e) {
                $error = $e->getMessage();
                $error_code = $e->getCode();
            }
            $out[$process] = [
                'name'      => $process,
                'alive'     => $is_alive,
                'error'     => $error,
                'errorCode' => $error_code,
            ];
        }

        return $out;
    }

    ////////////////////////////////////////////////////////////////////////


    protected function checkHealth_xcpd() {
        $this->checkFollower($this->xcpd_follower, 'Counterparty');
    }

    protected function checkHealth_native() {
        $this->checkFollower($this->xcpd_follower, 'Native');
    }

    protected function checkHealth_emaildaemon() {
        $this->checkHeartbeat('emaildaemon', 'Email Daemon');
    }

    protected function checkHealth_blockchaindaemon() {
        $this->checkHeartbeat('blockchaindaemon', 'Blockchain Daemon', 120); // 2 minutes
    }




    protected function checkFollower($follower, $desc) {
        $expected_block_count = $this->getBlockchainBlockCount();
        $actual_block_count = $follower->getLastProcessedBlock();

        $allowed_gap = 1;
        $actual_gap = $expected_block_count - $actual_block_count;
        if ($actual_gap > $allowed_gap) {
            throw new Exception("{$desc} follower is {$actual_gap} blocks behind block height of {$expected_block_count}. Last block processed was {$actual_block_count}.", 1);
        }
    }

    protected function checkHeartbeat($process_name, $process_desc=null, $allowed_length=null) {
        if ($process_desc === null) { $process_desc = $process_name; }

        if (!$this->heartbeat->processIsAlive($process_name, $allowed_length)) {
            $last_time = $this->heartbeat->getLastHeartbeatTime($process_name);
            if ($last_time) {
                $delay = time() - $last_time;
                $time_desc = gmdate("H:i:s", $delay);
            } else {
                $time_desc = 'more than 24 hours';
            }

            throw new Exception("Process {$process_desc} is not alive.  It has been down for {$time_desc}.", 1);
        }
    }

    protected function getBlockchainBlockCount() {
        return file_get_contents("https://blockchain.info/q/getblockcount");
    }

}

