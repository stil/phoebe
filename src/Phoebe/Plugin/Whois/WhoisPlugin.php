<?php
namespace Phoebe\Plugin\Whois;

use Phoebe\Event\Event;
use Phoebe\Plugin\PluginInterface;
use Phergie\Irc\Parser;

class WhoisPlugin implements PluginInterface
{
    protected $queue = [];
    protected $parser;

    public static function getSubscribedEvents()
    {
        return [
            'irc.received.311' => ['onWhois']
        ];
    }

    public function __construct()
    {
        $this->parser = new Parser();
    }

    public function whois($nickname, $writeStream, $callback)
    {
        $user = new ProcessedUser($nickname, $callback);
        $this->queue[$user->getId()] = $user;

        $writeStream->ircWhois('', $nickname);
    }

    public function onWhois(Event $event)
    {
        $msg = $event->getMessage();
        if (!isset($msg['params'][2]) || !isset($msg['tail'])) {
            return;
        }

        $id = strtolower($msg['params'][2]);
        if (isset($this->queue[$id])) {
            $user = $this->queue[$id];
            $tail = $msg['tail'];
            while (($reply = $this->parser->consume($tail)) !== null) {
                if ($reply['command'] != '318') {
                    $user->addWhoisReply($reply);
                } else {
                    break;
                }
            }
            unset($this->queue[$id]);
            $user->triggerCallback();
        }
    }
}
