<?php

namespace Phoebe\Examples;

use Phoebe\Event\Event;
use Phoebe\Plugin\PluginInterface;

class RelayPlugin implements PluginInterface
{
    /**
     * @var array
     */
    private $source;

    /**
     * @var array
     */
    private $destination;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'irc.received.001' => ['onWelcome'],
            'irc.received.PRIVMSG' => ['onMessage']
        ];
    }

    /**
     * @param string $channel
     * @param string $network
     */
    public function setSourceChannel($channel, $network)
    {
        $this->source = ['chan' => $channel, 'server' => $network];
    }

    /**
     * @param string $channel
     * @param string $network
     */
    public function setDestinationChannel($channel, $network)
    {
        $this->destination = ['chan' => $channel, 'server' => $network];
    }

    /**
     * @param Event $event
     * @throws \Exception
     */
    public function onWelcome(Event $event)
    {
        $this->ensureConfigured();

        if ($this->destination['server'] == $event->getConnection()->getServerHostname()) {
            $this->destination['writeStream'] = $event->getWriteStream();
        }
    }

    /**
     * @param Event $event
     */
    public function onMessage(Event $event)
    {
        $this->ensureConfigured();

        if ($this->source['server'] !== $event->getConnection()->getServerHostname()) {
            return;
        }

        $msg = $event->getMessage();
        if (!empty($msg['targets']) && $msg['targets'][0] == $this->source['chan']) {
            $this->destination['writeStream']->ircPrivmsg(
                $this->destination['chan'],
                "<{$msg['nick']}> {$msg['params']['text']}"
            );
        }
    }

    /**
     * @throws \Exception
     */
    private function ensureConfigured()
    {
        if (!isset($this->source) || !isset($this->destination)) {
            throw new \Exception('Set relay source and destination channel first.');
        }
    }
}