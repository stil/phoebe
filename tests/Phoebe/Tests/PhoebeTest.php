<?php
namespace Phoebe\Tests;

use Phoebe\Phoebe;
use Phoebe\Connection;

class PhoebeTest extends TestCase
{
    public function testAddConnection()
    {
        $client = new Phoebe;
        $this->assertEmpty($client->getConnections());

        $connection = new Connection;
        $client->addConnection($connection);

        $connections = $client->getConnections();

        $this->assertCount(1, $connections);
        $this->assertEquals($connection, $connections[0]);
    }
}
