<?php
namespace Phoebe\Plugin\AutoJoin;

use Traversable;

class AutoJoinList implements \IteratorAggregate, \Serializable, \Countable
{
    /**
     * @var array
     */
    protected $channels = [];

    /**
     * Add channel to auto join
     * @param string $channel Channel to auto join
     * @param null|string $key Channel password. NULL when ommited
     * @return $this
     */
    public function addChannel($channel, $key = null)
    {
        $channel = strtolower($channel);
        $this->channels[$channel] = $key;
        return $this;
    }

    /**
     * Add channels to auto join
     * @param array $channels Array of channels to join. Available formats:
     *                        array('#channel1', '#channel2') or
     *                        array('#channel1', '#channel2' => 'password') or
     *                        array('#channel1' => null, '#channel2' => 'password')
     * @return $this
     */
    public function addChannels($channels)
    {
        foreach ($channels as $k => $v) {
            if (preg_match('/^[0-9]+$/', $k)) {
                $this->addChannel($v);
            } else {
                $this->addChannel($k, $v);
            }
        }
        return $this;
    }

    /**
     * Removes channel from auto join list
     * @param string $channel Channel name
     * @return $this
     */
    public function removeChannel($channel)
    {
        $channel = strtolower($channel);
        if (isset($this->channels)) {
            unset($this->channels[$channel]);
        }
        return $this;
    }

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->channels);
    }

    /**
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        return json_encode($this->channels, JSON_PRETTY_PRINT);
    }

    /**
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        $this->channels = json_decode($serialized, true);
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return count($this->channels);
    }
}
