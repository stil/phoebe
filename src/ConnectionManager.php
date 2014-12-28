<?php
namespace Phoebe;

use Phergie\Irc\Client\React\Client;
use Phoebe\Event\EventBinder;
use Phoebe\Event\EventDispatcherTrait;

class ConnectionManager
{
    use EventDispatcherTrait;

    /**
     * Phergie React Client
     * @var Client
     */
    protected $client;

    /**
     * Timers object
     * @var Timers
     */
    protected $timers;

    /**
     * Creates instance of Phergie React Client
     */
    public function __construct()
    {
        $this->client = new Client();
        $this->timers = new Timers($this->client->getLoop());

        $binder = new EventBinder();
        $binder->bind($this->client, $this);
    }

    /**
     * Returns Phergie's Client instance
     * @return Client Client instance
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Returns Timers instance
     * @return Timers Timers instance
     */
    public function getTimers()
    {
        return $this->timers;
    }

    /**
     * Adds connection to Client
     * @param Connection $connection Connection instance
     * @return void
     */
    public function addConnection(Connection $connection)
    {
        $this->client->addConnection($connection);
    }

    /**
     * Starts the bot
     * @param  Connection[] $connections Array containing connections to add
     * @return void
     */
    public function run(array $connections = [])
    {
        $this->client->run($connections);
    }
}
