<?php
namespace Phoebe\Tests\Plugin\AutoJoin;

use Phoebe\Plugin\AutoJoin\AutoJoinList;
use Phoebe\Tests\TestCase;

class AutoJoinListTest extends TestCase
{
    public function testAddChannel()
    {
        $list = new AutoJoinList();
        $list->addChannel('#Channel');
        $list->addChannel('#channel');
        $list->addChannel('#chaNNel');
        $list->addChannel('#aNother');

        $count = 0;
        foreach ($list as $channel => $key) {
            if ($channel == '#channel') {
                $count++;
            }
        }

        $this->assertEquals(2, $list->count());
        $this->assertEquals(1, $count);
    }

    public function testSerialize()
    {
        $list = new AutoJoinList();
        $list->addChannel('#chaNNel');
        $list->addChannel('#aNoTher');

        $serialized = $list->serialize();

        $list = new AutoJoinList();
        $list->unserialize($serialized);

        $this->assertEquals(2, $list->count());

        foreach ($list as $channel => $key) {
            $this->assertTrue($channel == '#channel' || $channel == '#another');
        }
    }
}
