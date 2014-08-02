<?php
namespace Phoebe\Plugin\NickServ;

use Phoebe\Event\Event;
use Phoebe\Plugin\PluginInterface;

class NickServPlugin implements PluginInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'irc.received.001'    => ['onWelcome'],
            'irc.received.NOTICE' => ['onNotice']
        ];
    }

    public function __construct($password)
    {
        $this->password = $password;
    }

    public function onWelcome(Event $event)
    {
        $event->getWriteStream()->ircPrivmsg('NickServ', 'IDENTIFY '.$this->password);
    }

    public function onNotice(Event $event)
    {
        $msg = $event->getMessage();
        if (isset($msg['nick']) &&
            $msg['nick'] === 'NickServ' &&
            $msg['params']['text'] === 'Password accepted - you are now recognized.') {

            $event->getDispatcher()->dispatch('nickserv.identified', $event);
        }
    }
}
