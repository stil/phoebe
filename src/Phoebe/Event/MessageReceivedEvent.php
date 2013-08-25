<?php
namespace Phoebe\Event;

use Phergie\Irc\Client\React\WriteStream;

class MessageReceivedEvent extends AbstractMessageEvent
{
    protected $writeStream;

    public function setWriteStream(WriteStream $writeStream)
    {
        $this->writeStream = $writeStream;
    }

    public function getWriteStream()
    {
        return $this->writeStream;
    }
}
