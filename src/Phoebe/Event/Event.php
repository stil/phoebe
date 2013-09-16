<?php
namespace Phoebe\Event;

use Phoebe\ConnectionManager;
use Monolog\Logger;
use Phoebe\Timers;
use Phoebe\Connection;
use Symfony\Component\EventDispatcher\Event as SymfonyEvent;

class Event extends SymfonyEvent
{
    protected $connectionManager;
    protected $timers;
    protected $connection;
    protected $logger;
    protected $message;

    public function setConnectionManager(ConnectionManager $cm)
    {
        $this->connectionManager = $cm;
    }

    public function getConnectionManager()
    {
        return $this->connectionManager;
    }

    public function setTimers(Timers $timers)
    {
        $this->timers = $timers;
    }

    public function getTimers()
    {
        return $this->timers;
    }

    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }
}
