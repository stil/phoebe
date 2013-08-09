<?php
namespace Phoebe;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Phergie\Irc\Connection;
use Phergie\Irc\Client\React\Client;
use Phoebe\Event\Command;

class Phoebe extends EventDispatcher
{
    protected $client;

    public function __construct(Connection $conn)
    {
        $client = new Client;
        $client->addConnection($conn);
        $client->addListener(array($this, 'onClientEvent'));
        $this->client = $client;
    }

    public function onClientEvent($message, $write, $conn, $logger)
    {
        $event = new Command;
        $event->setMessage($message);
        $event->setWriteStream($write);
        $event->setConnection($conn);
        $event->setLogger($logger);

        $this->dispatch('cmd', $event);
        $this->dispatch('cmd.'.$message['command'], $event);
    }

    public function run()
    {
        $this->client->run();
    }
}
