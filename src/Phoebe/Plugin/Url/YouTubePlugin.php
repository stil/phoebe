<?php
namespace Phoebe\Plugin\Url;

use Phoebe\Event\Event;
use Phoebe\Formatter;
use Phoebe\Plugin\PluginInterface;
use cURL;
use Exception;

class YouTubePlugin implements PluginInterface
{
    protected $pattern = '%(?:youtube\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i';
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
    
    public function getFeed($videoId, $channel, $writeStream, $timers)
    {
        $ch = new cURL\Request('http://gdata.youtube.com/feeds/api/videos/'.$videoId.'?v=2&alt=json');
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
            $replace = array(
                '%title'    => $feed['entry']['title']['$t'],
                '%views'    => $this->formatBigNumber($feed['entry']['yt$statistics']['viewCount']),
                '%duration' => TimeDuration::get($feed['entry']['media$group']['yt$duration']['seconds']),
                '%likes'    => number_format($feed['entry']['yt$rating']['numLikes'], 0, '.', ','),
                '%dislikes' => number_format($feed['entry']['yt$rating']['numDislikes'], 0, '.', ',')
            );
            $response = ":: ".Formatter::bold(Formatter::color('You', 'black', 'white').Formatter::color('Tube', 'white', 'red'))." :: \x02%title\x02 (%duration) :: \x02%views\x02 views :: \x0303[+] %likes \x0304[-] %dislikes\x03 ::";
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

    public static function formatBigNumber($n)
    {
        $base = floor(log($n) / log(1000));
        $base = $base > 2 ? 2 : $base;
        $suffix = ['', 'k', 'M'][$base];
        $n = round($n / pow(1000, $base), 1);
        return $n.$suffix;
    }
}
