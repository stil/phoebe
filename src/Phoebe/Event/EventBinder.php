<?php
namespace Phoebe\Event;

use Phergie\Irc\Client\React\Client;
use Phoebe\ConnectionManager;
use Phoebe\Event\Event;
use Phoebe\Event\MessageReceivedEvent;
use Phoebe\Event\MessageSentEvent;

class EventBinder
{
    protected $client;
    protected $connectionManager;

    public function bind(Client $client, ConnectionManager $cm)
    {
        $this->client = $client;
        $this->connectionManager = $cm;
        $this->bindMethods(
            [
                'irc.received' => 'onIrcReceived',
                'irc.sent' => 'onIrcSent',
                'connect.before.all' => 'onConnectBeforeAfterAll',
                'connect.after.all' => 'onConnectBeforeAfterAll',
                'connect.before.each' => 'onConnectBeforeAfterEach',
                'connect.after.each' => 'onConnectBeforeAfterEach',
                'connect.error' => 'onConnectBeforeAfterEach'
            ]
        );
    }

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

        $event = new MessageReceivedEvent();
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
        list($message, $connection, $logger) = $args;

        $event = new MessageSentEvent();
        $event->setMessage($message);
        $event->setConnection($connection);
        $event->setLogger($logger);

        $targets = [$this->connectionManager, $connection];
        $this->dispatch($targets, [$eventName], $event);
    }

    protected function onConnectBeforeAfterAll(array $args, $eventName)
    {
        list($connections) = $args;

        $event = new Event();
        $event->connections = $connections;

        $targets = [$this->connectionManager];
        $this->dispatch($targets, [$eventName], $event);
    }

    protected function onConnectBeforeAfterEach(array $args, $eventName)
    {
        list($connection) = $args;

        $event = new Event();
        $event->setConnection($connection);

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
