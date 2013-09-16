Phoebe
======

Phoebe is IRC bot skeleton based on Phergie components.
The main advantage over Phergie 2 is its flexibility, which could be achieved with PHP namespaces.

Examples of use
===============

###Simple Phoebe bot example
```php
<?php
require __DIR__.'/vendor/autoload.php';

use Phoebe\ConnectionManager;
use Phoebe\Connection;
use Phoebe\Event\Event;
use Phoebe\Plugin\PingPong\PingPongPlugin;

$freenode = new Connection();
$freenode->setServerHostname('irc.freenode.net');
$freenode->setServerPort(6667);
$freenode->setNickname('Phoebe2');
$freenode->setUsername('Phoebe');
$freenode->setRealname('Phoebe');

// Create shortcut to EventDispatcher
$events = $freenode->getEventDispatcher();

// Add PingPongPlugin to avoid being kicked from server
$events->addSubscriber(new PingPongPlugin());

// Join #phoebe channel on startup
$events->addListener('irc.received.001', function (Event $event) {
    $event->getWriteStream()->ircJoin('#phoebe');
});

$phoebe = new ConnectionManager();
$phoebe->addConnection($freenode);
$phoebe->run();
```

###Multiple IRC networks
```php
<?php
require __DIR__.'/vendor/autoload.php';

use Phoebe\Connection;
use Phoebe\ConnectionManager;
use Phoebe\Plugin\PingPong\PingPongPlugin;
use Phoebe\Plugin\AutoJoin\AutoJoinPlugin;

// First connection to Rizon
// We enclose connections in functions to prevent namespace collisions
$rizon = function () {
    $conn = new Connection();
    $conn->setServerHostname('irc.rizon.net');
    $conn->setServerPort(6667);
    $conn->setNickname('Phoebe4');
    $conn->setUsername('Phoebe');
    $conn->setRealname('Phoebe');

    $events = $conn->getEventDispatcher();

    // We'll join several channels on startup
    $autoJoin = new AutoJoinPlugin();
    $autoJoin->addChannels(
        ['#channel1' => 'key', '#channel2', '#channel3']
    );
    $events->addSubscriber($autoJoin);

    // Answer on "hi" with "hello, nick"
    $events->addListener(
        'irc.received.PRIVMSG',
        function ($event) {
            $msg = $event->getMessage();
            if ($msg['params']['text'] == 'hi') {
                $event->getWriteStream()->ircPrivmsg(
                    $event->getSource(),
                    'hello, '.$msg['nick']
                );
            }
        }
    );

    return $conn;
};

// Second connection to QuakeNet
$qn = function () {
    $conn = new Connection();
    $conn->setServerHostname('irc.quakenet.org');
    $conn->setServerPort(6667);
    $conn->setNickname('Phoebe5');
    $conn->setUsername('Phoebe');
    $conn->setRealname('Phoebe');

    return $conn;
};

// Now create instance of ConnectionManager and add previously prepared connections
$phoebe = new ConnectionManager();
$phoebe->addConnection($rizon());
$phoebe->addConnection($qn());

// You can also listen global events on ConnectionManager
$events = $phoebe->getEventDispatcher();

// PingPongPlugin will prevent us from disconnecting from server
$events->addSubscriber(new PingPongPlugin());

// We can start the bot now
$phoebe->run();
```

Event object
============

Below you can check which methods are available at different events

| Method / event name         | `irc.received.*` | `irc.sent` | `connection.error` |
| --------------------------: |:----------------:| :---------:| :-----------------:|
| `getMessage()`              | yes              | yes        | yes                |
| `getConnectionManager()`    | yes              | yes        | yes                |
| `getConnection()`           | yes              | yes        | yes                |
| `getTimers()`               | yes              | yes        | yes                |
| `getLogger()`               | yes              | yes        | yes                |
| `getWriteStream()`          | yes              | no         | no                 |          

Creating your own plugins
=========================

Plugin class has just to implement getSubscribedEvents() method.

Here is example of simple plugin:
```php
<?php
use Phoebe\Event\Event;
use Phoebe\Plugin\PluginInterface;

class HelloPlugin implements PluginInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            'irc.received.PRIVMSG' => array('onMessage', 0),
            'irc.received.NOTICE'  => array('onMessage', 0)
        );
    }

    public function onMessage(Event $event)
    {
        $msg = $event->getMessage();
        if ($msg['params']['text'] === 'hello') {
            $event->getWriteStream()->ircPrivmsg(
                $msg['nick'],
                'Hi!'
            );
        }
    }
}
```
