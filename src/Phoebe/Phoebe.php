<?php
namespace Phoebe;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Phergie\Irc\Client\React\Client;
use Phoebe\Event\Event;
use Phoebe\Event\MessageReceivedEvent;
use Phoebe\Event\MessageSentEvent;

class Phoebe extends EventDispatcher
{
    /**
     * Phergie React Client
     * 
     * @var Phergie\Irc\Client\React\Client
     */
    protected $client;

    /**
     * Array containing Connection objects
     * @var Phergie\Irc\Connection[]
     */
    protected $connections = array();

    /**
     * Adds connection to client
     * @param Connection $connection Connection object
     * @return void
     */
    public function addConnection(Connection $connection)
    {
        $this->connections[] = $connection;
    }

    /**
     * Returns all connections
     * @return Phergie\Irc\Connection[]
     */
    public function getConnections()
    {
        return $this->connections;
    }

    public function onMessageReceived($message, $writeStream, $connection, $logger)
    {
        $event = new MessageReceivedEvent;
        $event->setMessage($message);
        $event->setWriteStream($writeStream);
        $event->setConnection($connection);
        $event->setLogger($logger);

        $connection->dispatch('irc.received', $event);
        $connection->dispatch('irc.received.'.$message['command'], $event);

        $this->dispatch('irc.received', $event);
        $this->dispatch('irc.received.'.$message['command'], $event);
    }

    public function onMessageSent($message, $connection, $logger)
    {
        $event = new MessageSentEvent;
        $event->setMessage($message);
        $event->setConnection($connection);
        $event->setLogger($logger);

        $connection->dispatch('irc.sent', $event);
        $this->dispatch('irc.sent', $event);
    }

    /**
     * Starts the bot
     * 
     * @return void
     */
    public function run()
    {
        $self = $this;
        $client = new Client;
        
        $client->on(
            'irc.received',
            array($this, 'onMessageReceived')
        );

        $client->on(
            'irc.sent',
            array($this, 'onMessageSent')
        );

        foreach (array('before', 'after') as $eventType) {
            $client->on(
                'connect.'.$eventType.'.all',
                function ($connections) use ($self, $eventType) {
                    $event = new Event;
                    $event->connections = $connections;
                    $self->dispatch('connect.'.$eventType.'.all', $event);
                }
            );

            $client->on(
                'connect.'.$eventType.'.each',
                function ($connection) use ($self, $eventType) {
                    $event = new Event;
                    $event->connection = $connection;
                    $self->dispatch('connect.'.$eventType.'.each', $event);
                    $connection->dispatch('connect.'.$eventType.'.each', $event);
                }
            );
        }

        $client->on(
            'connect.error',
            function ($message, $connection, $logger) use ($self) {
                $event = new Event;
                $event->connection = $connection;
                $event->logger     = $logger;
                $event->message    = $message;
                $self->dispatch('connect.error', $event);
                $connection->dispatch('connect.error', $event);
            }
        );

        $this->client = $client;
        $this->client->run($this->connections);
    }
}
