<?php
namespace Phoebe\Plugin\Url;

use Phoebe\Event\Event;
use Phoebe\Formatter;
use Phoebe\Plugin\PluginInterface;
use cURL;
use Exception;

class SpotifyPlugin implements PluginInterface
{
    protected $pattern = '#(?:spotify:track:|http\:\/\/open\.spotify\.com\/track\/)([a-zA-Z0-9]+)#';
    protected $active  = false;
    protected $queue;
    
    public static function getSubscribedEvents()
    {
        return [
            'irc.received.PRIVMSG' => ['onMessage']
        ];
    }

    public function __construct()
    {
        $this->queue = new cURL\RequestsQueue;
        $this->queue->getDefaultOptions()->set(
            array(
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_CONNECTTIMEOUT  => 3,
                CURLOPT_ENCODING        => ''
            )
        );
        
        $this->queue->addListener(
            'complete',
            array($this, 'dataReady')
        );
    }
    
    protected function socketPerform($timers)
    {
        $self = $this;
        try {
            $active = $this->queue->socketPerform();
        } catch (Exception $e) {

        }
        if ($active) {
            $timers->setTimeout(
                function () use ($self, $timers) {
                    $self->socketPerform($timers);
                },
                0.1
            );
        }
    }
    
    public function getFeed($uri, $channel, $writeStream, $timers)
    {
        $ch = new cURL\Request('http://ws.spotify.com/lookup/1/.json?uri=spotify:track:'.$uri);
        $ch->_chan = $channel;
        $ch->_writeStream = $writeStream;
        $this->queue->attach($ch);

        $this->socketPerform($timers);
    }
    
    public function dataReady(cURL\Event $event)
    {
        $writeStream = $event->request->_writeStream;
        $channel = $event->request->_chan;

        $res = $event->response;
        $code = $res->getInfo(CURLINFO_HTTP_CODE);
        $feed = $res->getContent();
        if ($code == 200 && !empty($feed)) {
            $feed = json_decode($feed, true);
            $track = $feed['track'];

            $replace = [
                '%title'    => Formatter::bold($track['artists'][0]['name'].' - '.Formatter::underline($track['name'])),
                '%duration' => TimeDuration::get((int)$track['length']),
                '%album'    => $track['album'] ? Formatter::bold($track['album']['name']).' ('.$track['album']['released'].')' : 'n/d'
            ];

            $response = ":: ".Formatter::bold(Formatter::color(' Spotify ', 'white', 'green'))." :: %title (%duration) :: Album: %album";
            $writeStream->ircPrivmsg($channel, strtr($response, $replace));
        }
    }
    
    public function onMessage(Event $event)
    {
        $msg = $event->getMessage();
        if ($msg->isInChannel() && $msg->matchText($this->pattern, $matches)) {
            $id = $matches[1];
            $this->getFeed(
                $id,
                $msg->getSource(),
                $event->getWriteStream(),
                $event->getTimers()
            );
        }
    }
}
