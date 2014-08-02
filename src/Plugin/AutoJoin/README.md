AutoJoin allows you to manage list of channels to be joined automatically on connection startup. It's per connection plugin.

#####Basic usage example
```php
$conn = new Connection();
$conn->setServerHostname('irc.rizon.net');
$conn->setServerPort(6667);
$conn->setNickname('Phoebe2');
$conn->setUsername('Phoebe');
$conn->setRealname('Phoebe');

// Create shortcut to EventDispatcher
$events = $freenode->getEventDispatcher();

// Add PingPongPlugin to avoid being kicked from server
$events->addSubscriber(new PingPongPlugin());

// Create AutoJoinPlugin instance
$autoJoin = new AutoJoinPlugin();

// Add some channels to autojoin list
$autoJoin->addChannel('#news');
$autoJoin->addChannel('#help');
$autoJoin->addChannel('#secret', 'password');

// Add instance of plugin to created connection
$events->addSubscriber($autoJoin);
```

#####Use with NickServ plugin (manual trigger)
```php
// Create AutoJoinPlugin instance, but configure it to NOT trigger automatically
$autoJoin = new AutoJoinPlugin(false);

// Add some channels to autojoin list
$autoJoin->addChannel('#news');
$autoJoin->addChannel('#help');
$autoJoin->addChannel('#secret', 'password');

// Add instance of plugin to created connection
$events->addSubscriber($autoJoin);

// Configure NickServPlugin to login when connected to network
$events->addSubscriber(new NickServPlugin('secretpassword'));

// Listen on NickServ identified event
$events->addListener('nickserv.identified', function ($event) use ($autoJoin) {
    // Trigger autojoin when identified to NickServ
    $autoJoin->trigger($event->getWriteStream());
});
```
