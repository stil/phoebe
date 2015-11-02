<?php
namespace Phoebe\Plugin\PingPong;

use Phoebe\Event\Event;
use Phoebe\Plugin\PluginInterface;

class PingPongPlugin implements PluginInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return ['irc.received.PING' => ['onPing']];
    }

    /**
     * @param Event $event
     */
    public function onPing(Event $event)
    {
        $pongMessage = $event->getMessage()['params']['server1'];
        $event->getWriteStream()->ircPong($pongMessage);
    }
}
