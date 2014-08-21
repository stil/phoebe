<?php
namespace Phoebe\Plugin\AutoReconnect;

class ConnectionState
{
    protected $lastActivity = null;

    public function touch()
    {
        $this->lastActivity = time();
    }

    public function needsReconnect($reconnectTimeout)
    {
        return $this->lastActivity !== null &&
            (time()-$this->lastActivity >= $reconnectTimeout);
    }
}
