<?php
namespace Phoebe\Event;

use Symfony\Component\EventDispatcher\Event as SymfonyEvent;
use Phoebe\ConnectionManager;
use Phoebe\Connection;
use Phoebe\Timers;
use Monolog\Logger;
use Phergie\Irc\Client\React\WriteStream;

class Event extends SymfonyEvent
{
    protected $connectionManager;
    protected $connection;
    protected $timers;
    protected $logger;
    protected $message;
    protected $writeStream;

    /**
     * Stores instance of ConnectionManager
     * 
     * @param ConnectionManager $connectionManager
     */
    public function setConnectionManager(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    /**
     * Returns instance of ConnectionManager
     * 
     * @return ConnectionManager
     */
    public function getConnectionManager()
    {
        return $this->connectionManager;
    }

    /**
     * Stores instance of Connection which caused this Event
     * 
     * @param Connection $connection
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Returns instance of Connection which caused this Event
     * 
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Stores instance of Timers
     * 
     * @param Timers $timers
     */
    public function setTimers(Timers $timers)
    {
        $this->timers = $timers;
    }

    /**
     * Returns instance of Timers
     * 
     * @param Timers $timers
     */
    public function getTimers()
    {
        return $this->timers;
    }

    /**
     * Stores instance of Logger
     * 
     * @param Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Returns instance of Logger
     * 
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Stores message containing information on this Event
     * 
     * @param array|object $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * Returns message containing information on this Event
     * 
     * @return array|object
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Stores instance of WriteStream which handles current connection
     * 
     * @param WriteStream $writeStream
     */
    public function setWriteStream(WriteStream $writeStream)
    {
        $this->writeStream = $writeStream;
    }

    /**
     * Returns instance of WriteStream which handles current connection
     * 
     * @return WriteStream
     */
    public function getWriteStream()
    {
        return $this->writeStream;
    }
}
