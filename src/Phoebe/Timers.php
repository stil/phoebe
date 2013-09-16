<?php
namespace Phoebe;

use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;

class Timers
{
    protected $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    /**
     * Delays execution of callback by specified amount of seconds
     * @param callback $callback Callback function
     * @param int $interval Time interval in seconds
     * @return TimerInterface
     */
    public function setTimeout($callback, $interval)
    {
        return $this->loop->addTimer($interval, $callback);
    }

    /**
     * Executes callback every specified amount of seconds
     * @param callback $callback Callback function
     * @param int $interval Time interval in seconds
     * @return TimerInterface
     */
    public function setInterval($callback, $interval)
    {
        return $this->loop->addPeriodicTimer($interval, $callback);
    }

    /**
     * Cancels previously set timer
     * @param  TimerInterface $timer Timer object to cancel
     * @return void
     */
    public function cancel($timer)
    {
        return $this->loop->cancelTimer($timer);
    }
}
