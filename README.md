Phoebe
======
Phoebe is an IRC bot skeleton based on Phergie components.
The main advantage over Phergie 2 is its flexibility, which could be achieved with PHP namespaces.

#### Table of contents
* [Examples of use](#examples-of-use)
 * [Simple Phoebe bot](#simple-phoebe-bot)
 * [Multiple IRC networks](#multiple-irc-networks)
* [Event object](#event-object)
* [Plugins](#plugins)
 * [List of plugins](#list-of-plugins)
 * [Creating custom plugins](#creating-custom-plugins)
* [Using Timers](#using-timers)


## Examples of use

### Simple Phoebe bot
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

### Multiple IRC networks
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

## Event object
Below you can check which methods are available at different events

| Method / event name         | `irc.received.*` | `irc.sent` | `connection.error` |
| --------------------------: |:----------------:| :---------:| :-----------------:|
| `getMessage()`              | yes              | yes        | yes                |
| `getConnectionManager()`    | yes              | yes        | yes                |
| `getConnection()`           | yes              | yes        | yes                |
| `getTimers()`               | yes              | yes        | yes                |
| `getLogger()`               | yes              | yes        | yes                |
| `getWriteStream()`          | yes              | no         | no                 |          

## Plugins

### List of plugins
* [`Phoebe\Plugin\PingPong\PingPongPlugin`](https://github.com/stil/phoebe/blob/master/src/Phoebe/Plugin/PingPong/PingPongPlugin.php) - keeps connection alive by responding to server PING messages
* [`Phoebe\Plugin\UserInfo\UserInfoPlugin`](https://github.com/stil/phoebe/blob/master/src/Phoebe/Plugin/UserInfo/UserInfoPlugin.php) - tracks information on users joining and parting the channels and their flags (+o, +v etc.)
* [`Phoebe\Plugin\AutoJoin\AutoJoinPlugin`](https://github.com/stil/phoebe/blob/master/src/Phoebe/Plugin/AutoJoin/AutoJoinPlugin.php) - allows you to configure easily channels which have to be joined on startup
* [`Phoebe\Plugin\NickServ\NickServPlugin`](https://github.com/stil/phoebe/blob/master/src/Phoebe/Plugin/NickServ/NickServPlugin.php) - identifies to NickServ on startup
* [`Phoebe\Plugin\Url\YouTubePlugin`](https://github.com/stil/phoebe/blob/master/src/Phoebe/Plugin/YouTubePlugin.php) - displays information on YouTube links mentioned on channel
* [`Phoebe\Plugin\Url\SpotifyPlugin`](https://github.com/stil/phoebe/blob/master/src/Phoebe/Plugin/SpotifyPlugin.php) - displays information on Spotify links/URIs mentioned on channel

Do you know plugins worth spreading? Add them to list above through pull request! (but keep similiar form: class name with link - description)

### Creating custom plugins
Plugin class has just to implement `getSubscribedEvents()` method.

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

## Using Timers
There are situations, when you need to delay execution of particular function. Thanks to Timers class it is very easy in Phoebe.

Below you can see how to reconnect to IRC with short delay.
```php
$reconnect = function ($event) {
    $hostname = $event->getConnection()->getServerHostname();
    $event->getLogger()->debug(
        "Connection to $hostname lost, attempting to reconnect in 15 seconds.\n"
    );

    $event->getTimers()->setTimeout(
        function () use ($event) { // Use $event so we have access to required objects
            $event->getLogger()->debug("Reconnecting now...\n");
            $event->getConnectionManager()->addConnection(
                $event->getConnection()
            );
        },
        15 // Execute callback after 15 seconds
    );
};

// Reconnect when there is connection problem
$events->addListener('irc.received.ERROR', $reconnect);
$events->addListener('connect.error', $reconnect);
```
