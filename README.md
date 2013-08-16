Phoebe
======

Phoebe is IRC bot skeleton based on Phergie components.
The main advantage over Phergie is its flexibility, which could be achieved with PHP namespaces.

Getting started
===============

Example of the simplest Phoebe bot:
```php
<?php
require __DIR__.'/vendor/autoload.php';

use Phergie\Irc\Connection;
use Phoebe\Phoebe;
use Phoebe\Plugin\PingPong\PingPongPlugin;

// Let's create connection
$conn = new Connection;
$conn->setServerHostname('irc.rizon.net');
$conn->setServerPort(6668);
$conn->setNickname('Phoebe2');
$conn->setUsername('Phoebe');
$conn->setRealname('Phoebe');

$phoebe = new Phoebe;
$phoebe->addConnection($conn);

// Add PingPong plugin to avoid being kicked from server
$phoebe->addSubscriber(new PingPongPlugin);
$phoebe->run();
```

Creating your own plugins
=========================

Plugins in the fact are objects implementing `Symfony\Component\EventDispatcher\EventSubscriberInterface`.
They listen for particular events and proccess them respectively.
It's recommended for the plugins to extend `Phoebe\Plugin\Plugin` class (so you don't need to implement EventSubscriberInterface).

Here is example of simple plugin:
```php
<?php
use Phoebe\Event\Event;
use Phoebe\Plugin\Plugin;

class HelloPlugin extends Plugin
{
    public static function getSubscribedEvents()
    {
        return array(
            'cmd.PRIVMSG' => array('onMessage', 0),
            'cmd.NOTICE' => array('onMessage', 0)
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

Then you should add your plugin:

```php
$phoebe->addSubscriber(new HelloPlugin);
```

Plugins on particular connections
=================================

Sometimes you need bot working on several IRC networks paralelly.
You might need have different plugins on each connection. Phoebe can be easily set up, to solve this problem.

Look at the example:
```php
<?php
require __DIR__.'/vendor/autoload.php';

use Phergie\Irc\Connection;
use Phoebe\Phoebe;
use Phoebe\Plugin\PingPong\PingPongPlugin;
use Phoebe\Plugin\UserInfo\UserInfoPlugin;

$phoebe = new Phoebe;
// First connection
$conn1 = new Connection;
$conn1->setServerHostname('irc.rizon.net');
$conn1->setServerPort(6667);
$conn1->setNickname('Phoebe4');
$conn1->setUsername('Phoebe');
$conn1->setRealname('Phoebe');
$phoebe->addConnection($conn1, 'rizon'); // Second parameter is connection ID

// Second connection
$conn2 = new Connection;
$conn2->setServerHostname('irc.quakenet.org');
$conn2->setServerPort(6667);
$conn2->setNickname('Phoebe5');
$conn2->setUsername('Phoebe');
$conn2->setRealname('Phoebe');
$phoebe->addConnection($conn2, 'qn');

// It will join channel #phoebe on both connections
$phoebe->addListener('cmd.001', function ($event) {
    $event->getWriteStream()->ircJoin('#phoebe');
});

// PingPong will be working also on both conections
$phoebe->addSubscriber(new PingPongPlugin, array('*'));
// or by default $phoebe->addSubscriber(new PingPongPlugin);


// We're adding separate instances of UserInfoPlugin to each network
$info1 = new UserInfoPlugin;
$info1->setDebugMode(true);
$phoebe->addSubscriber($info1, array('rizon'));

$info2 = new UserInfoPlugin;
$info2->setDebugMode(true);
$phoebe->addSubscriber($info2, array('qn'));

// And start the bot
$phoebe->run();
```

There are two methods
