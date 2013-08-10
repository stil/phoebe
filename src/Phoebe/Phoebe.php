<?php
namespace Phoebe;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Phergie\Irc\Connection;
use Phergie\Irc\Client\React\Client;
use Phoebe\Event\Command;

class Phoebe
{
    /**
     * Phergie React Client
     * 
     * @var Phergie\Irc\Client\React\Client
     */
    protected $client;

    /**
     * Array containing EventDispatcher objects grouped by keys
     * 
     * @var array
     */
    protected $dispatchers = array();

    /**
     * Constructor method
     */
    public function __construct()
    {
        $this->client = new Client;
        $this->client->addListener(array($this, 'onClientEvent'));

        /**
         * Global dispatcher
         */
        $this->dispatchers['*'] = new EventDispatcher;
    }

    /**
     * Adds connection to client
     * @param Connection $connection Instance of Phergie\Irc\Connection
     * @param string     $id         ID of connection (NULL if doesn't matter)
     * @return void
     */
    public function addConnection(Connection $connection, $id = null)
    {
        if ($id !== null) {
            if (!isset($this->dispatchers[$id])) {
                $this->dispatchers[$id] = new EventDispatcher;
            }

            $connection->_dispatcher = $this->dispatchers[$id];
        }
        $this->client->addConnection($connection);
    }

    /**
     * Adds subscriber to desired connections
     * @param EventSubscriberInterface $subscriber    Event subscriber object
     * @param array                    $dispatcherIds Array of listened connections' IDs.
     *                                                "*" means all connections.
     *                                                By default it subscribes to all connections.
     * @return void
     */
    public function addSubscriber(EventSubscriberInterface $subscriber, $dispatcherIds = array('*'))
    {
        foreach ($dispatcherIds as $id) {
            if (isset($this->dispatchers[$id])) {
                $this->dispatchers[$id]->addSubscriber($subscriber);
            }
        }
    }

    /**
     * Adds listener to desired connections
     * 
     * @param string   $eventName     The event to listen on
     * @param callable $listener      The listener
     * @param integer  $priority      The higher this value, the earlier an event listener will be triggered in the chain (defaults to 0)
     * @param array    $dispatcherIds Array of listened connections' IDs.
     *                                "*" means all connections.
     *                                By default it listens to all connections.
     * @return void
     */
    public function addListener($eventName, $listener, $priority = 0, $dispatcherIds = array('*'))
    {
        foreach ($dispatcherIds as $id) {
            if (isset($this->dispatchers[$id])) {
                $this->dispatchers[$id]->addListener($eventName, $listener);
            }
        }
    }

    public function onClientEvent($message, $write, $conn, $logger)
    {
        $event = new Command;
        $event->setMessage($message);
        $event->setWriteStream($write);
        $event->setConnection($conn);
        $event->setLogger($logger);

        $this->dispatchers['*']->dispatch('cmd', $event);
        $this->dispatchers['*']->dispatch('cmd.'.$message['command'], $event);

        if (isset($conn->_dispatcher)) {
            $localDispatcher = $conn->_dispatcher;
            $localDispatcher->dispatch('cmd', $event);
            $localDispatcher->dispatch('cmd.'.$message['command'], $event);
        }
    }

    /**
     * Runs the bot
     * 
     * @return void
     */
    public function run()
    {
        $this->client->run();
    }
}
