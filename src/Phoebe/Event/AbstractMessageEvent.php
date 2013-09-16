<?php
namespace Phoebe\Event;

abstract class AbstractMessageEvent extends Event
{
    /**
     * Determines whether a given string is a valid IRC channel name.
     *
     * @param string $string String to analyze
     *
     * @return bool TRUE if $string contains a valid channel name, FALSE
     *         otherwise
     */
    protected function isChannelName($string)
    {
        // Per the 2000 RFCs 2811 and 2812, channels may begin with &, #, +, or !
        return (strspn($string, '#&+!', 0, 1) >= 1);
    }

    /**
     * Returns the channel name if the event occurred in a channel or the
     * user nick if the event was a private message directed at the bot by a
     * user.
     *
     * @return string
     */
    public function getSource()
    {
        $msg = $this->getMessage();
        
        if (isset($msg['targets'][0]) && $this->isChannelName($msg['targets'][0])) {
            return $msg['targets'][0];
        } else {
            return $msg['nick'];
        }
    }

    /**
     * Returns whether or not the event occurred within a channel.
     *
     * @return TRUE if the event is in a channel, FALSE otherwise
     */
    public function isInChannel()
    {
        return $this->isChannelName($this->getSource());
    }
}
