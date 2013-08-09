<?php
namespace Phoebe\Plugin\NickServ;

use Phoebe\Event\Event;
use Phoebe\Plugin\Plugin;

class NickServPlugin extends Plugin
{
    public static function getSubscribedEvents()
    {
        return array(
            'cmd.001' => array('onWelcome', 0),
            'cmd.NOTICE' => array('onNotice', 0)
        );
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
