<?php
namespace Phoebe\Plugin\Url;

use Phergie\Irc\Client\React\WriteStream;
use Phoebe\Plugin\Async\HttpAsyncPlugin;
use cURL;

class YouTubePlugin extends UrlPlugin
{
    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @param HttpAsyncPlugin $async
     * @param string $apiKey Google API key.
     * More information: https://developers.google.com/console/help/#generatingdevkeys
     */
    public function __construct(HttpAsyncPlugin $async, $apiKey)
    {
        $this->apiKey = $apiKey;
        parent::__construct($async);
    }

    /**
     * @return string
     */
    public function getMessagePattern()
    {
        return '%(?:youtube\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i';
    }

    /**
     * @param array $matches
     * @param string $channel
     * @param WriteStream $writeStream
     */
    public function processMessage(array $matches, $channel, WriteStream $writeStream)
    {
        $videoId = $matches[1];

        $query = http_build_query([
            'id' => $videoId,
            'part' => 'snippet,contentDetails,statistics',
            'key' => $this->apiKey
        ]);

        $req = new cURL\Request('https://www.googleapis.com/youtube/v3/videos?' . $query);
        $req->addListener('complete', function (cURL\Event $event) use ($writeStream, $channel) {
            $this->onRequestComplete($event, $channel, $writeStream);
        });

        $this->sendRequest($req);
    }

    /**
     * @param cURL\Event $event
     * @param $channel
     * @param WriteStream $writeStream
     */
    public function onRequestComplete(cURL\Event $event, $channel, WriteStream $writeStream)
    {
        $res = $event->response;
        $code = $res->getInfo(CURLINFO_HTTP_CODE);
        $feed = $res->getContent();

        if ($code != 200 || empty($feed)) {
            return;
        }

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

    /**
     * Strips video URL, preventing bot wars.
     * @param string $title
     * @return string
     */
    protected function stripUrl($title)
    {
        return preg_replace($this->getMessagePattern(), '', $title);
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
