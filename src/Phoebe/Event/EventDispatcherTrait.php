<?php
namespace Phoebe\Event;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

trait EventDispatcherTrait
{
    protected $eventDispatcher = null;

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getEventDispatcher()
    {
        if ($this->eventDispatcher === null) {
            $this->eventDispatcher = new EventDispatcher();
        }

        return $this->eventDispatcher;
    }
}
