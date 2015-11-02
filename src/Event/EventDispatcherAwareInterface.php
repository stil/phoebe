<?php
namespace Phoebe\Event;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

interface EventDispatcherAwareInterface
{
    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher);

    /**
     * @return null|EventDispatcherInterface
     */
    public function getEventDispatcher();
}
