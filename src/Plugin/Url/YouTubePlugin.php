<?php
namespace Phoebe\Plugin\Url;

use Phoebe\Plugin\Async\HttpAsyncPlugin;
use cURL;

class YouTubePlugin extends UrlPlugin
{
    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected static $anyUrlPattern = '/((([A-Za-z]{3,9}:(?:\/\/)?)(?:[\-;:&=\+\$,\w]+@)?[A-Za-z0-9\.\-]+|(?:www\.|'.
        '[\-;:&=\+\$,\w]+@)[A-Za-z0-9\.\-]+)((?:\/[\+~%\/\.\w\-_]*)?\??(?:[\-\+=&;%@\.\w_]*)#?(?:[\.\!\/\\\w]*))?)/';

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
     * @param ChannelContext $context
     */
    public function processMessage(array $matches, ChannelContext $context)
    {
        $videoId = $matches[1];

        $query = http_build_query([
            'id' => $videoId,
            'part' => 'snippet,contentDetails,statistics',
            'key' => $this->apiKey
        ]);

        $req = new cURL\Request('https://www.googleapis.com/youtube/v3/videos?' . $query);
        $req->addListener('complete', function (cURL\Event $event) use ($context) {
            $this->onRequestComplete($event, $context);
        });

        $this->sendRequest($req);
    }

    /**
     * @param cURL\Event $event
     * @param ChannelContext $context
     */
    public function onRequestComplete(cURL\Event $event, ChannelContext $context)
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

        if (preg_match(self::$anyUrlPattern, $item['snippet']['title'])) {
            $context->getLogger()->notice("Blocked possible YouTube loop.");
            return;
        }

        $replace = array(
            '%title'    => $item['snippet']['title'],
            '%views'    => $this->formatBigNumber($item['statistics']['viewCount']),
            '%duration' => TimeDuration::format($duration),
            '%likes'    => number_format($item['statistics']['likeCount'], 0, '.', ','),
            '%dislikes' => number_format($item['statistics']['dislikeCount'], 0, '.', ',')
        );

        $response =
            "\x02\x0301,00You\x0300,04Tube\x03  %title\x02 (%duration), \x02%views\x02 views,".
            " \x0303\x02▲\x02 %likes \x0304\x02▼\x02 %dislikes\x03";
        $context->send(strtr($response, $replace));
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
