<?php
namespace Phoebe\Plugin\Url;

use Phergie\Irc\Client\React\WriteStream;
use Phoebe\Formatter;
use cURL;

class SpotifyPlugin extends UrlPlugin
{
    /**
     * @return string
     */
    public function getMessagePattern()
    {
        return '#(?:spotify:track:|https?\:\/\/open\.spotify\.com\/track\/)([a-zA-Z0-9]+)#';
    }

    /**
     * @param array $matches
     * @param string $channel
     * @param WriteStream $writeStream
     */
    public function processMessage(array $matches, $channel, WriteStream $writeStream)
    {
        $uri = $matches[1];
        $req = new cURL\Request('http://ws.spotify.com/lookup/1/.json?uri=spotify:track:'.$uri);
        $req->addListener('complete', function (cURL\Event $event) use ($writeStream, $channel) {
            $this->onRequestComplete($event, $channel, $writeStream);
        });

        $this->sendRequest($req);
    }

    /**
     * @param cURL\Event $event
     * @param string $channel
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
        $track = &$feed['track'];

        $replace = [
            '%artist'   => $track['artists'][0]['name'],
            '%track'    => $track['name'],
            '%duration' => TimeDuration::get((int)$track['length']),
            '%album'    => $track['album'] ? $track['album']['name'] : 'n/d',
            '%released' => $track['album']['released']
        ];

        $response = strtr(
            '<b><color fg="white" bg="green"> Spotify </color></b>  '.
            '<b>%artist - <u>%track</u></b> (%duration), album: <b>%album</b> (%released)',
            $replace
        );

        $writeStream->ircPrivmsg($channel, Formatter::parse($response));
    }
}
