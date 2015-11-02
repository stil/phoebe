<?php
namespace Phoebe\Plugin\Async;

use cURL\Request;
use cURL\RequestsQueue;
use Phoebe\Plugin\PluginInterface;

class HttpAsyncPlugin implements PluginInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return ['irc.tick' => ['onTick']];
    }

    /**
     * @var RequestsQueue
     */
    protected $queue;

    public function __construct()
    {
        $this->queue = new RequestsQueue();
        $this->queue->getDefaultOptions()->set([
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_CONNECTTIMEOUT  => 3,
            CURLOPT_ENCODING        => ''
        ]);
    }

    /**
     * @param Request $request
     */
    public function send(Request $request)
    {
        $this->queue->attach($request);
    }

    public function onTick()
    {
        if ($this->queue->count() > 0) {
            $this->queue->socketPerform();
        }
    }
}
