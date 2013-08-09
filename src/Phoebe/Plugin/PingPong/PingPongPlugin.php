<?php
namespace Phoebe\Plugin\PingPong;

use Phoebe\Event\Event;
use Phoebe\Plugin\Plugin;

class PingPongPlugin extends Plugin
{
    public static function getSubscribedEvents()
    {
        return array(
            'cmd.PING' => array('onPing', 0)
        );
    }

    public function onPing(Event $event)
    {
        $pongMessage = $event->getMessage()['params']['all'];
        $event->getWriteStream()->ircPong($pongMessage);
    }
} 
