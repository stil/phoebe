<?php
namespace Phoebe\Plugin\Url;

use Phergie\Irc\Client\React\WriteStream;
use Phoebe\Event\Event;
use Psr\Log\LoggerInterface;

class ChannelContext
{
    /**
     * @var string
     */
    protected $channel;

    /**
     * @var WriteStream
     */
    protected $writeStream;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @return WriteStream
     */
    public function getWriteStream()
    {
        return $this->writeStream;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param Event $event
     */
    public function __construct(Event $event)
    {
        $this->channel = $event->getMessage()->getSource();
        $this->writeStream = $event->getWriteStream();
        $this->logger = $event->getLogger();
    }

    /**
     * @param string $msg
     */
    public function send($msg)
    {
        $this->writeStream->ircPrivmsg($this->channel, $msg);
    }
}
