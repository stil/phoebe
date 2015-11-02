<?php
namespace Phoebe\Plugin\Url;

use Phergie\Irc\Client\React\WriteStream;
use Phoebe\Event\Event;
use Phoebe\FloodProtection\RateLimit;
use Phoebe\FloodProtection\Throttling;
use Phoebe\Plugin\PluginInterface;
use cURL;
use Exception;
use Phoebe\Timers;

class YouTubePlugin implements PluginInterface
{
    /**
     * @var string
     */
    protected $pattern = '%(?:youtube\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i';

    /**
     * @var bool
     */
    protected $active  = false;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var cURL\RequestsQueue
     */
    protected $queue;

    /**
     * @var Throttling
     */
    protected $throttling;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return ['irc.received.PRIVMSG' => ['onMessage']];
    }

    /**
     * @param string $apiKey Google API key.
     * More information: https://developers.google.com/console/help/#generatingdevkeys
     */
    public function __construct($apiKey)
    {
        $this->throttling = new Throttling();
        $this->throttling->addRateLimit(new RateLimit(2, 10));

        $this->apiKey = $apiKey;
        $this->queue = new cURL\RequestsQueue();
        $this->queue->getDefaultOptions()->set([
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_CONNECTTIMEOUT  => 3,
            CURLOPT_ENCODING        => ''
        ]);
        
        $this->queue->addListener('complete', [$this, 'dataReady']);
    }

    /**
     * @return Throttling
     */
    public function getThrottling()
    {
        return $this->throttling;
    }

    /**
     * @param Throttling $throttling
     */
    public function setThrottling($throttling)
    {
        $this->throttling = $throttling;
    }

    /**
     * @param Timers $timers
     */
    protected function socketPerform(Timers $timers)
    {
        try {
            if ($this->queue->socketPerform()) {
                $timers->setTimeout(function () use ($timers) {
                    $this->socketPerform($timers);
                }, 0.1);
            }
        } catch (Exception $e) {
        }
    }

    /**
     * @param string $videoId
     * @param string $channel
     * @param WriteStream $writeStream
     * @param Timers $timers
     */
    public function getFeed($videoId, $channel, WriteStream $writeStream, Timers $timers)
    {
        $query = http_build_query([
            'id' => $videoId,
            'part' => 'snippet,contentDetails,statistics',
            'key' => $this->apiKey
        ]);

        $ch = new cURL\Request('https://www.googleapis.com/youtube/v3/videos?'.$query);
        $ch->_chan = $channel;
        $ch->_writeStream = $writeStream;
        $this->queue->attach($ch);

        $this->socketPerform($timers);
    }

    /**
     * @param cURL\Event $event
     */
    public function dataReady(cURL\Event $event)
    {
        /**
         * @var WriteStream $writeStream
         */
        $writeStream = $event->request->_writeStream;
        $channel = $event->request->_chan;

        $res = $event->response;
        $code = $res->getInfo(CURLINFO_HTTP_CODE);
        $feed = $res->getContent();
        if ($code == 200 && !empty($feed)) {
            $feed = json_decode($feed, true);

            if (!isset($feed['items'][0])) {
                return;
            }

            $item = $feed['items'][0];
            $duration = new \DateInterval($item['contentDetails']['duration']);
            $replace = array(
                '%title'    => $this->stripUrl($item['snippet']['title']),
                '%views'    => $this->formatBigNumber($item['statistics']['viewCount']),
                '%duration' => TimeDuration::format($duration),
                '%likes'    => number_format($item['statistics']['likeCount'], 0, '.', ','),
                '%dislikes' => number_format($item['statistics']['dislikeCount'], 0, '.', ',')
            );

            $response =
                "\x02\x0301,00You\x0300,04Tube\x03  %title\x02 (%duration), \x02%views\x02 views,".
                " \x0303\x02▲\x02 %likes \x0304\x02▼\x02 %dislikes\x03";
            $writeStream->ircPrivmsg($channel, strtr($response, $replace));
        }
    }

    /**
     * Strips video URL, preventing bot wars.
     * @param string $title
     * @return string
     */
    protected function stripUrl($title)
    {
        return preg_replace($this->pattern, '', $title);
    }

    /**
     * @param Event $event
     */
    public function onMessage(Event $event)
    {
        if ($this->throttling->limitsExceeded()) {
            return;
        }

        $msg = $event->getMessage();
        $matches = [];
        if ($msg->isInChannel() && $msg->matchText($this->pattern, $matches)) {
            $videoId = $matches[1];
            $this->throttling->tick();
            $this->getFeed(
                $videoId,
                $msg->getSource(),
                $event->getWriteStream(),
                $event->getTimers()
            );
        }
    }

    /**
     * @param $n
     * @return string
     */
    public static function formatBigNumber($n)
    {
        if ($n < 1000) {
            return $n;
        }

        $base = floor(log($n) / log(1000));
        $base = $base > 2 ? 2 : $base;
        $suffix = ['', 'k', 'M'][$base];
        $n = round($n / pow(1000, $base), 1);
        return $n.$suffix;
    }
}
