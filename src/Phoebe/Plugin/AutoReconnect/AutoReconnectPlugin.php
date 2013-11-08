<?php
namespace Phoebe\Plugin\AutoReconnect;

use Phoebe\Event\Event;
use Phoebe\Plugin\PluginInterface;
use SplObjectStorage;

class AutoReconnectPlugin implements PluginInterface
{
    protected $lastPingTime;
    protected $pingTimeout = 240;
    protected $reconnectDelay = 60;

    public static function getSubscribedEvents()
    {
        return [
            'irc.received'  => ['onPing'],
            'connect.error' => ['onDisconnect']
        ];
    }

    public function __construct()
    {
        $this->lastPingTime = new SplObjectStorage();
    }

    protected function reconnect($event)
    {
        $hostname = $event->getConnection()->getServerHostname();
        $event->getLogger()->debug(
            "Connection to $hostname lost, attempting to reconnect in 15 seconds.\n"
        );

        $event->getTimers()->setTimeout(
            function () use ($event) {
                $event->getLogger()->debug("Reconnecting now...\n");
                $event->getConnectionManager()->addConnection(
                    $event->getConnection()
                );
            },
            $this->reconnectDelay
        );
    }

    public function onDisconnect(Event $event)
    {
        $this->reconnect($event);
    }

    public function onPing(Event $event)
    {
        $now = time();
        $this->lastPingTime[$event->getConnection()] = $now;
        $self = $this;

        $event->getTimers()->setTimeout(
            function () use ($event, $now, $self) {
                $before = $now;
                $after = $self->lastPingTime[$event->getConnection()];
                if ($before == $after) {
                    $self->reconnect($event);
                }
            },
            $this->pingTimeout
        );
    }
}
