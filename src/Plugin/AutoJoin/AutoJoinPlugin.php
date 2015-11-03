<?php
namespace Phoebe\Plugin\AutoJoin;

use Phoebe\Event\Event;
use Phoebe\Plugin\PluginInterface;
use Phergie\Irc\Client\React\WriteStream;

class AutoJoinPlugin implements PluginInterface
{
    /**
     * @var AutoJoinList
     */
    protected $list;

    /**
     * @var bool
     */
    protected $autoTrigger;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return ['irc.received.001' => ['onWelcome']];
    }

    /**
     * @param boolean $autoTrigger Set FALSE when you want to manually trigger autojoin.
     */
    public function __construct($autoTrigger = true)
    {
        $this->autoTrigger = $autoTrigger;
        $this->list = new AutoJoinList();
    }

    /**
     * @param Event $event
     */
    public function onWelcome(Event $event)
    {
        if ($this->autoTrigger) {
            $this->trigger($event->getWriteStream());
        }
    }

    /**
     * Joins all the channels added to auto join list
     * @param  WriteStream $writeStream WriteStream object
     * @return void
     */
    public function trigger(WriteStream $writeStream)
    {
        foreach ($this->list as $channel => $key) {
            $writeStream->ircJoin($channel, $key);
        }
    }

    /**
     * @return AutoJoinList
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param AutoJoinList $list
     */
    public function setList($list)
    {
        $this->list = $list;
    }
}
