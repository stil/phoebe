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
