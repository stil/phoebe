<?php
namespace Phoebe\Event;

use Phergie\Irc\Client\React\WriteStream;
use Phergie\Irc\Connection;
use Monolog\Logger;

class Command extends Event
{
    protected $message;
    protected $write;
    protected $connection;
    protected $logger;

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setWriteStream(WriteStream $writeStream)
    {
        $this->write = $writeStream;
    }

    public function getWriteStream()
    {
        return $this->write;
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
}
