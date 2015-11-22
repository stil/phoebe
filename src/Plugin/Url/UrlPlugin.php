<?php
namespace Phoebe\Plugin\Url;

use cURL\Request;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\VoidCache;
use Phoebe\Event\Event;
use Phoebe\FloodProtection\RateLimit;
use Phoebe\FloodProtection\Throttling;
use Phoebe\Plugin\Async\HttpAsyncPlugin;
use Phoebe\Plugin\PluginInterface;

abstract class UrlPlugin implements UrlPluginInterface, PluginInterface
{
    /**
     * @var HttpAsyncPlugin
     */
    private $async;

    /**
     * @var Throttling
     */
    protected $throttling;

    /**
     * @var Cache
     */
    protected $cacheDriver;

    /**
     * @param HttpAsyncPlugin $async
     */
    public function __construct(HttpAsyncPlugin $async)
    {
        $this->async = $async;

        $this->throttling = new Throttling();
        $this->throttling->addRateLimit(new RateLimit(2, 10));

        $this->cacheDriver = new ArrayCache();
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return ['irc.received.PRIVMSG' => ['onMessage']];
    }

    /**
     * @param Request $request
     */
    protected function sendRequest(Request $request)
    {
        if (!$this->throttling->limitsExceeded()) {
            $this->throttling->tick();
            $this->async->send($request);
        }
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
    public function setThrottling(Throttling $throttling)
    {
        $this->throttling = $throttling;
    }

    /**
     * @return Cache
     */
    public function getCacheDriver()
    {
        return $this->cacheDriver instanceof Cache ? $this->cacheDriver : new VoidCache();
    }

    /**
     * @param Cache $cacheDriver
     */
    public function setCacheDriver($cacheDriver)
    {
        $this->cacheDriver = $cacheDriver;
    }

    /**
     * @param Event $event
     */
    public function onMessage(Event $event)
    {
        $msg = $event->getMessage();
        $matches = [];
        if ($msg->isInChannel() && $msg->matchText($this->getMessagePattern(), $matches)) {
            $this->processMessage($matches, new ChannelContext($event));
        }
    }
}
