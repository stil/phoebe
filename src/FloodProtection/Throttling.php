<?php
namespace Phoebe\FloodProtection;

class Throttling
{
    /**
     * @var RateLimit[]
     */
    protected $rateLimits = [];

    /**
     * @param RateLimit $limit
     */
    public function addRateLimit(RateLimit $limit)
    {
        $this->rateLimits[] = $limit;
    }

    public function clearRateLimits()
    {
        $this->rateLimits = [];
    }

    /**
     * @return bool
     */
    public function limitsExceeded()
    {
        foreach ($this->rateLimits as $limit) {
            if ($limit->exceeded()) {
                return true;
            }
        }
        return false;
    }

    public function tick()
    {
        foreach ($this->rateLimits as $limit) {
            $limit->update();
        }
    }
}
