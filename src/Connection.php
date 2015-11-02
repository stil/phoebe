<?php
namespace Phoebe;

use Phergie\Irc\Connection as PhergieConnection;
use Phoebe\Event\EventDispatcherAwareInterface;
use Phoebe\Event\EventDispatcherAwareTrait;

class Connection extends PhergieConnection implements ConnectionInterface, EventDispatcherAwareInterface
{
    use EventDispatcherAwareTrait;
}
