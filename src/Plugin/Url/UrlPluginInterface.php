<?php
namespace Phoebe\Plugin\Url;

use Phergie\Irc\Client\React\WriteStream;

interface UrlPluginInterface
{
    /**
     * @return string
     */
    public function getMessagePattern();

    /**
     * @param array $matches
     * @param string $channel
     * @param WriteStream $writeStream
     */
    public function processMessage(array $matches, $channel, WriteStream $writeStream);
}
