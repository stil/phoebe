<?php
namespace Phoebe;

use Phergie\Irc\Client\React\Client;
use Phoebe\Event\EventBinder;
use Phoebe\Event\EventDispatcherAwareInterface;
use Phoebe\Event\EventDispatcherAwareTrait;

class ConnectionManager implements EventDispatcherAwareInterface
{
    use EventDispatcherAwareTrait;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Timers
     */
    protected $timers;

    /**
     * Creates instance of Phergie React Client and binds events
     */
    public function __construct()
    {
        $this->client = new Client();
        $this->timers = new Timers($this->client->getLoop());

        $binder = new EventBinder();
        $binder->bind($this->client, $this);
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return Timers
     */
    public function getTimers()
    {
        return $this->timers;
    }

    /**
     * @param ConnectionInterface $connection
     * @return void
     */
    public function addConnection(ConnectionInterface $connection)
    {
        $this->client->addConnection($connection);
    }

    /**
     * @param ConnectionInterface[] $connections
     * @return void
     */
    public function run(array $connections = [])
    {
        $this->client->run($connections);
    }
}
