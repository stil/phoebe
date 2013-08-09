<?php
namespace Phoebe\Plugin\PingPong;

use Phoebe\Event;
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
        $event->write->ircPong($event->message['params']['all']);
    }
} 
