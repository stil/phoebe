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

use Phoebe\Phoebe;
use Phoebe\Connection;
use Phoebe\Event\Event;
use Phoebe\Plugin\PingPong\PingPongPlugin;

$freenode = new Connection;
$freenode->setServerHostname('irc.freenode.net');
$freenode->setServerPort(6667);
$freenode->setNickname('Phoebe2');
$freenode->setUsername('Phoebe');
$freenode->setRealname('Phoebe');

// Join #phoebe channel on IRC welcome message
$freenode->addListener('irc.received.001', function (Event $event) {
    $event->getWriteStream()->ircJoin('#phoebe');
});

$phoebe = new Phoebe;
$phoebe->addConnection($freenode);

// Add PingPong plugin to avoid being kicked from server
$phoebe->addSubscriber(new PingPongPlugin);
$phoebe->run();
```

Creating your own plugins
=========================

All plugins must only implement getSubscribedEvents() method.

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

Then you should add your plugin:

```php
$phoebe->addSubscriber(new HelloPlugin); // Add plugin to all connections
```

Plugins on multiple connections
=================================

Sometimes you need bot working on several IRC networks paralelly.
You might need have different plugins on each connection. Phoebe can be easily set up to solve this problem.

Look at the example:
```php
<?php
require __DIR__.'/vendor/autoload.php';

use Phoebe\Connection;
use Phoebe\Phoebe;
use Phoebe\Plugin\PingPong\PingPongPlugin;
use Phoebe\Plugin\UserInfo\UserInfoPlugin;

// First connection
$rizon = new Connection;
$rizon->setServerHostname('irc.rizon.net');
$rizon->setServerPort(6667);
$rizon->setNickname('Phoebe4');
$rizon->setUsername('Phoebe');
$rizon->setRealname('Phoebe');

// Second connection
$qn = new Connection;
$qn->setServerHostname('irc.quakenet.org');
$qn->setServerPort(6667);
$qn->setNickname('Phoebe5');
$qn->setUsername('Phoebe');
$qn->setRealname('Phoebe');

/**
 * We're creating instance of Phoebe and adding our connections
 */
$phoebe = new Phoebe;
$phoebe->addConnection($rizon);
$phoebe->addConnection($qn);

/**
 * PingPongPlugin should listen on both connections.
 */
$phoebe->addSubscriber(new PingPongPlugin);

// It will join channel #phoebe on both connections.
$phoebe->addListener('irc.received.001', function ($event) {
    $event->getWriteStream()->ircJoin('#phoebe');
});

// And start the bot
$phoebe->run();
```
