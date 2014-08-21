<?php
namespace Phoebe\Plugin\AutoReconnect;

use Phoebe\Event\Event;
use Phoebe\Plugin\PluginInterface;

class AutoReconnectPlugin implements PluginInterface
{
    protected $connectionTimeout = 180;

    public static function getSubscribedEvents()
    {
        return [
            'irc.tick' => ['onTick'],
            'irc.received' => ['onActivity'],
        ];
    }

    public function setTimeout($timeout)
    {
        $this->connectionTimeout = $timeout;
    }

    protected function extractState(Event $event)
    {
        $state = $event->getConnection()->getOption('state');
        if ($state === null) {
            $state = new ConnectionState();
            $event->getConnection()->setOption('state', $state);
        }
        return $state;
    }

    public function onTick(Event $event)
    {
        $state = $this->extractState($event);

        if ($state->needsReconnect($this->connectionTimeout)) {
            $event->getWriteStream()->ircQuit('Reconnecting...');
            $event->getConnectionManager()->addConnection(
                $event->getConnection()
            );
            $state->touch();
        }
    }

    public function onActivity(Event $event)
    {
        $state = $this->extractState($event);
        $state->touch();
    }
}
