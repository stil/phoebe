<?php
namespace Phoebe\Plugin\Whois;

use Phoebe\Event\Event;
use Phoebe\Plugin\PluginInterface;
use SplQueue;

class WhoisPlugin implements PluginInterface
{
    protected $queue;
    protected $whoisInProgress = false;

    public static function getSubscribedEvents()
    {
        return [
            'irc.received.311' => ['onWhoisBegin'],
            'irc.received.318' => ['onWhoisEnd'],
            'irc.received'     => ['onMessage']
        ];
    }

    public function __construct()
    {
        $this->queue = new SplQueue();
    }

    public function whois($nickname, $writeStream, $callback)
    {
        $this->queue->enqueue(
            new ProcessedUser($nickname, $callback)
        );

        $writeStream->ircWhois('', $nickname);
    }

    public function onWhoisBegin(Event $event)
    {
        $this->whoisInProgress = true;
    }

    public function onMessage(Event $event)
    {
        if ($this->whoisInProgress == false || $this->queue->isEmpty()) {
            return;
        }
        
        $this->queue->bottom()->addWhoisReply(
            $event->getMessage()
        );
    }

    public function onWhoisEnd(Event $event)
    {
        $this->whoisInProgress = false;
        $user = $this->queue->bottom();
        $this->queue->dequeue();
        $user->triggerCallback();
    }
}
