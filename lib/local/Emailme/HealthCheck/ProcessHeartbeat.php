<?php

namespace Emailme\HealthCheck;

use EmailMe\Debug\Debug;
use Exception;

/*
* ProcessHeartbeat
*/
class ProcessHeartbeat
{

    protected $expire = 86400;

    ////////////////////////////////////////////////////////////////////////

    public function __construct($redis) {
        $this->redis = $redis;
    }

    public function beat($process_name) {
        $this->redis->SETEX('beat/'.$process_name, $this->expire, time());
    }

    public function processIsAlive($process_name, $allowed_length=null) {
        if ($allowed_length === null) { $allowed_length = 60; }
        $last_beat = $this->getLastHeartbeatTime($process_name);
        if ($last_beat === null) { return false; }
        $time_since_last_beat = time() - $last_beat;
        if ($time_since_last_beat > $allowed_length) { return false; }

        return true;
    }

    public function getLastHeartbeatTime($process_name) {
        return $this->redis->GET('beat/'.$process_name);
    }

    ////////////////////////////////////////////////////////////////////////

}

