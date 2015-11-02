<?php
namespace Phoebe\Event;

use Phoebe\ConnectionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Event as SymfonyEvent;
use Phoebe\ConnectionManager;
use Phoebe\Connection;
use Phoebe\Timers;
use Monolog\Logger;
use Phergie\Irc\Client\React\WriteStream;

class Event extends SymfonyEvent
{
    /**
     * @var ConnectionManager
     */
    protected $connectionManager;

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var Timers
     */
    protected $timers;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var
     */
    protected $message;

    /**
     * @var WriteStream
     */
    protected $writeStream;

    /**
     * @param ConnectionManager $connectionManager
     */
    public function setConnectionManager(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    /**
     * @return ConnectionManager
     */
    public function getConnectionManager()
    {
        return $this->connectionManager;
    }

    /**
     * @param Connection $connection
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return Connection Instance of Connection which caused this Event
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param Timers $timers
     */
    public function setTimers(Timers $timers)
    {
        $this->timers = $timers;
    }

    /**
     * @return Timers
     */
    public function getTimers()
    {
        return $this->timers;
    }

    /**
     * @param Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param array|object $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return array|object
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param WriteStream $writeStream
     */
    public function setWriteStream(WriteStream $writeStream)
    {
        $this->writeStream = $writeStream;
    }

    /**
     * @return WriteStream
     */
    public function getWriteStream()
    {
        return $this->writeStream;
    }
}
