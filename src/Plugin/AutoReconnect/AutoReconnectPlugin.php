<?php
namespace Phoebe\Plugin\AutoReconnect;

use Phoebe\Event\Event;
use Phoebe\Plugin\PluginInterface;
use SplObjectStorage;

class AutoReconnectPlugin implements PluginInterface
{
    protected $debug;
    protected $connections;
    protected $pingTimeout = 240;
    protected $reconnectDelay = 60;

    public static function getSubscribedEvents()
    {
        return [
            //'irc.received.PING'  => ['onPing'],
            //'irc.received.001'  => ['onWelcome'],
            'connect.error'  => ['onError']
        ];
    }

    public function __construct()
    {
        $this->connections = new SplObjectStorage();
    }

    public function onWelcome(Event $event)
    {
        // Start tracking pings only after IRC welcome message
        $this->connections[$event->getConnection()] = time();
    }

    public function onPing(Event $event)
    {
        $conn = $event->getConnection();
        if (!isset($this->connections[$conn])) {
            return; // ignore if there wasn't welcome message
        }

        $now = time();
        $this->connections[$conn] = $now;
        $event->getTimers()->setInterval(
            function () use ($event, $now, $self) {
                $before = $now;
                $after = $self->lastPingTime[$event->getConnection()];
                
                $self->lastPingTime[$event->getConnection()] = time();

                if ($before == $after) {
                    $self->reconnect($event);
                }
            },
            $this->pingTimeout
        );
    }

    protected function reconnect($event)
    {
        $hostname = $event->getConnection()->getServerHostname();
        $event->getLogger()->debug(
            "Connection to $hostname lost, attempting to reconnect in {$this->reconnectDelay} seconds.\n"
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

    public function onError(Event $event)
    {
        var_dump($event);
    }
}
