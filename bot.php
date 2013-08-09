<?php
require __DIR__.'/vendor/autoload.php';

use Phergie\Irc\Connection;
use Phoebe\Phoebe;
use Phoebe\Plugin\PingPong\PingPongPlugin;
use Phoebe\Plugin\NickServ\NickServPlugin;

$conn = new Connection;
$conn->setServerHostname('irc.rizon.net');
$conn->setServerPort(6668);
$conn->setNickname('Phoebe2');
$conn->setUsername('Phoebe');
$conn->setRealname('Phoebe');

$phoebe = new Phoebe;
$phoebe->addConnection($conn);
$phoebe->addSubscriber(new PingPongPlugin);
//$phoebe->addSubscriber(new NickServPlugin('nickservPasword'));

while (true) {
    $phoebe->run();
    sleep(10);
}
