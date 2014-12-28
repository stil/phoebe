<?php
namespace Phoebe\Event;

use Phergie\Irc\Client\React\Client;
use Phoebe\ConnectionManager;
use Phoebe\Message\ReceivedMessage;

class EventBinder
{
    /**
     * Instance of Phergie client
     * @var Client
     */
    protected $client;

    /**
     * Instance of Phoebe connection manager
     * @var ConnectionManager
     */
    protected $connectionManager;

    /**
     * Binds all events of Phergie client to Phoebe connection manager
     * @param  Client            $client Phergie client
     * @param  ConnectionManager $cm     Phoebe connection manager
     * @return void
     */
    public function bind(Client $client, ConnectionManager $cm)
    {
        $this->client = $client;
        $this->connectionManager = $cm;
        $this->bindMethods([
            'irc.sent' => 'onIrcSent',
            'irc.received' => 'onIrcReceived',
            'irc.tick' => 'onIrcTick',
            'connect.end' => 'onConnectEnd',
            'connect.error' => 'onConnectError',
            'connect.before.each' => 'onConnectBeforeEach',
            'connect.after.each'  => 'onConnectAfterEach'
        ]);
    }

    /**
     * Binds Phergie events to the dispatcher
     * @param  array  $binds Array of the binded events
     * @return void
     */
    protected function bindMethods(array $binds)
    {
        $self = $this;
        foreach ($binds as $eventName => $methodName) {
            $this->client->on(
                $eventName,
                function () use ($self, $methodName, $eventName) {
                    $self->{$methodName}(func_get_args(), $eventName);
                }
            );
        }
    }

    protected function dispatch(array $targets, array $eventNames, Event $event)
    {
        $event->setConnectionManager($this->connectionManager);
        $event->setTimers($this->connectionManager->getTimers());

        foreach ($targets as $target) {
            $ed = $target->getEventDispatcher();
            foreach ($eventNames as $eventName) {
                $ed->dispatch($eventName, $event);
            }
        }
    }

    protected function onIrcReceived(array $args, $eventName)
    {
        list($message, $writeStream, $connection, $logger) = $args;

        $message = new ReceivedMessage($message);
        $event = new Event();
        $event->setMessage($message);
        $event->setWriteStream($writeStream);
        $event->setConnection($connection);
        $event->setLogger($logger);

        $targets = [$this->connectionManager, $connection];
        $eventNames = [$eventName.'.'.$message['command'], $eventName];
        $this->dispatch($targets, $eventNames, $event);
    }

    protected function onIrcSent(array $args, $eventName)
    {
        list($message, $writeStream, $connection, $logger) = $args;

        $event = new Event();
        $event->setMessage($message);
        $event->setWriteStream($writeStream);
        $event->setConnection($connection);
        $event->setLogger($logger);

        $targets = [$this->connectionManager, $connection];
        $this->dispatch($targets, [$eventName], $event);
    }

    protected function onIrcTick(array $args, $eventName)
    {
        list($writeStream, $connection, $logger) = $args;

        $event = new Event();
        $event->setWriteStream($writeStream);
        $event->setConnection($connection);
        $event->setLogger($logger);

        $targets = [$this->connectionManager, $connection];
        $this->dispatch($targets, [$eventName], $event);
    }

    protected function onConnectBeforeEach(array $args, $eventName)
    {
        list($connection) = $args;

        $event = new Event();
        $event->setConnection($connection);

        $targets = [$this->connectionManager, $connection];
        $this->dispatch($targets, [$eventName], $event);
    }

    protected function onConnectAfterEach(array $args, $eventName)
    {
        list($connection, $writeStream) = $args;

        $event = new Event();
        $event->setConnection($connection);
        $event->setWriteStream($writeStream);

        $targets = [$this->connectionManager, $connection];
        $this->dispatch($targets, [$eventName], $event);
    }

    protected function onConnectEnd(array $args, $eventName)
    {
        list($connection, $logger) = $args;

        $event = new Event();
        $event->setConnection($connection);
        $event->setLogger($logger);

        $targets = [$this->connectionManager, $connection];
        $this->dispatch($targets, [$eventName], $event);
    }

    protected function onConnectError(array $args, $eventName)
    {
        list($message, $connection, $logger) = $args;

        $event = new Event();
        $event->setConnection($connection);
        $event->setMessage($message);
        $event->setLogger($logger);

        $targets = [$this->connectionManager, $connection];
        $this->dispatch($targets, [$eventName], $event);
    }
}
