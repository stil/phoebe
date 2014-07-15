<?php
namespace Phoebe;

use Phergie\Irc\Connection as PhergieConnection;
use Phoebe\Event\EventDispatcherTrait;

class Connection extends PhergieConnection
{
    use EventDispatcherTrait;
}
