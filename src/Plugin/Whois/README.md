Whois
======
This plugin gives you convenient interface to accessing WHOIS replies.
Whois plugin is per-connection plugin, not global.

###Example of use
```php
<?php
require __DIR__.'/vendor/autoload.php';

use Phoebe\ConnectionManager;
use Phoebe\Connection;
use Phoebe\Event\Event;
use Phoebe\Plugin\PingPong\PingPongPlugin;
use Phoebe\Plugin\Whois\WhoisPlugin;

$freenode = new Connection();
$freenode->setServerHostname('irc.rizon.net');
$freenode->setServerPort(6667);
$freenode->setNickname('Phoebe2');
$freenode->setUsername('Phoebe');
$freenode->setRealname('Phoebe');

// Create shortcut to EventDispatcher
$events = $freenode->getEventDispatcher();

// Add PingPongPlugin to avoid being kicked from server
$events->addSubscriber(new PingPongPlugin());

// Add whois plugin to Event Dispatcher
$whoisPlugin = new WhoisPlugin();
$events->addSubscriber($whoisPlugin);

// When connected to network, do WHOIS
$events->addListener('irc.received.001', function (Event $event) use ($whoisPlugin) {
    $whoisPlugin->whois(
        'lipvig', // WHOISed nickname
        $event->getWriteStream(),
        function ($info) {
            $isIdentified = isset($info[307]); // TRUE or FALSE whether user is identified to NickServ
            $channels = explode(' ', $info[319]['params'][3]); // list of joined channels
            var_dump($isIdentified, $channels);
        }
    );
});

$phoebe = new ConnectionManager();
$phoebe->addConnection($freenode);
$phoebe->run();
```

Refer to [IRC numeric codes](https://www.alien.net.au/irc/irc2numerics.html) to recognize WHOIS responses.

