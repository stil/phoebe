<?php
namespace Phoebe\Message;

use ArrayObject;

class ReceivedMessage extends ArrayObject
{
    /**
     * Determines whether a given string is a valid IRC channel name.
     * @param string $string String to analyze
     * @return bool TRUE if $string contains a valid channel name, FALSE otherwise
     */
    protected function isChannelName($string)
    {
        // Per the 2000 RFCs 2811 and 2812, channels may begin with &, #, +, or !
        return (strspn($string, '#&+!', 0, 1) >= 1);
    }

    /**
     * Returns the channel name if the event occurred in a channel or the user nick
     * if the event was a private message directed at the bot by a user.
     * @return string
     */
    public function getSource()
    {
        if (isset($this['targets'][0]) && $this->isChannelName($this['targets'][0])) {
            return $this['targets'][0];
        } else {
            return $this['nick'];
        }
    }

    /**
     * @return TRUE if the event is in a channel, FALSE otherwise
     */
    public function isInChannel()
    {
        return $this->isChannelName($this->getSource());
    }

    /**
     * Executes regular expression match on message text
     * @param  string   $pattern  The pattern to search for
     * @param  array    $matches  Variable which will be filled with search results
     * @return int|bool           Returns number of times when pattern matches or FALSE on error
     */
    public function matchText($pattern, &$matches = [])
    {
        if (isset($this['params']['text'])) {
            return preg_match($pattern, $this['params']['text'], $matches);
        } else {
            return false;
        }
    }
}
