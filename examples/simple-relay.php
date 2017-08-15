<?php
require __DIR__ . '/../vendor/autoload.php';

use Phoebe\Connection;
use Phoebe\ConnectionManager;
use Phoebe\Examples\RelayPlugin;
use Phoebe\Plugin\PingPong\PingPongPlugin;
use Phoebe\Plugin\AutoJoin\AutoJoinPlugin;

// First connection to irc.rizon.net
$conn1 = function () {
    $conn = new Connection();
    $conn->setServerHostname('irc.rizon.net');
    $conn->setServerPort(6667);
    $conn->setNickname('Phoebe' . mt_rand(1000, 9999));
    $conn->setUsername('user');
    $conn->setRealname('user');

    $autoJoin = new AutoJoinPlugin();
    $autoJoin->getList()->addChannel('#phoebe_relay_test_src');

    $events = $conn->getEventDispatcher();
    $events->addSubscriber($autoJoin);

    return $conn;
};

// Second connection to irc.quakenet.org
$conn2 = function () {
    $conn = new Connection();
    $conn->setServerHostname('irc.quakenet.org');
    $conn->setServerPort(6667);
    $conn->setNickname('Phoebe' . mt_rand(1000, 9999));
    $conn->setUsername('user');
    $conn->setRealname('user');

    $autoJoin = new AutoJoinPlugin();
    $autoJoin->getList()->addChannel('#phoebe_relay_test_dst');

    $events = $conn->getEventDispatcher();
    $events->addSubscriber($autoJoin);

    return $conn;
};

// Now create instance of ConnectionManager and add previously prepared connections.
$phoebe = new ConnectionManager();
$phoebe->addConnection($conn1());
$phoebe->addConnection($conn2());

// Global event dispatcher.
$events = $phoebe->getEventDispatcher();

// PingPongPlugin prevents from disconnecting from IRC server.
$events->addSubscriber(new PingPongPlugin());

// Relay plugin.
$relayPlugin = new RelayPlugin();
$relayPlugin->setSourceChannel('#phoebe_relay_test_src', 'irc.rizon.net');
$relayPlugin->setDestinationChannel('#phoebe_relay_test_dst', 'irc.quakenet.org');

$events->addSubscriber($relayPlugin);

// Now we can start the bot.
$phoebe->run();