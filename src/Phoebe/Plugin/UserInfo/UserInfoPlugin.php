<?php
namespace Phoebe\Plugin\UserInfo;

use Phoebe\Event\Event;
use Phoebe\Plugin\PluginInterface;

/**
 * Provides an API for querying information on users.
 * Originally created by Phergie Development Team <team@phergie.org>
 * Modified by stil
 */

class UserInfoPlugin implements PluginInterface
{
    const REGULAR = 1;
    const VOICE   = 2;
    const HALFOP  = 4;
    const OP      = 8;
    const ADMIN   = 16;
    const OWNER   = 32;

    public static function getSubscribedEvents()
    {
        return array(
            'irc.received.PRIVMSG'  => array('onPrivmsg', 0),
            'irc.received.MODE' => array('onMode', 0),
            'irc.received.JOIN' => array('onJoin', 0),
            'irc.received.PART' => array('onPart', 0),
            'irc.received.QUIT' => array('onQuit', 0),
            'irc.received.NICK' => array('onNick', 0),
            'irc.received.353'  => array('onNameReply', 0)
        );
    }

    /**
     * Debug mode: TRUE or FALSE
     * 
     * @var boolean
     */
    protected $debug = false;

    /**
     * An array containing all the user information for a given channel
     *
     * @var array
     */
    protected $store = array();

    /**
     * Sets debug mode
     * 
     * @param bool $mode Debug mode (TRUE or FALSE)
     */
    public function setDebugMode($mode)
    {
        $this->debug = (bool)$mode;
    }

    /**
     * Tracks mode changes
     *
     * @return void
     */
    public function onMode(Event $event)
    {
        $msg = $event->getMessage();

        if (!isset($msg['params']['channel']) || !isset($msg['params']['user'])) {
            return;
        }

        $chan = $msg['params']['channel'];
        $nicks = $msg['params']['user'];
        $modes = $msg['params']['mode'];

        if (!preg_match('/(?:\+|-)[hovaq+-]+/i', $modes)) {
            return;
        }

        $chan = trim(strtolower($chan));
        $modes = str_split(trim(strtolower($modes)), 1);
        $nicks = explode(' ', trim($nicks));
        $operation = array_shift($modes); // + or -

        while ($char = array_shift($modes)) {
            $nick = array_shift($nicks);
            $mode = null;

            switch ($char) {
                case 'q':
                    $mode = self::OWNER;
                    break;
                case 'a':
                    $mode = self::ADMIN;
                    break;
                case 'o':
                    $mode = self::OP;
                    break;
                case 'h':
                    $mode = self::HALFOP;
                    break;
                case 'v':
                    $mode = self::VOICE;
                    break;
            }

            if (!empty($mode)) {

                // Unknow users - temp fix
                if (!isset($this->store[$chan][$nick])) {
                    $this->store[$chan][$nick] = self::REGULAR;
                }

                if ($operation == '+') {
                    $this->store[$chan][$nick] |= $mode;
                } else if ($operation == '-') {
                    $this->store[$chan][$nick] ^= $mode;
                }
            }
        }
    }

    /**
     * Tracks users joining a channel
     *
     * @return void
     */
    public function onJoin(Event $event)
    {
        $msg = $event->getMessage();

        if (!isset($msg['nick'])) {
            return;
        }

        $chan = strtolower($msg['params']['channels']);
        $nick = $msg['nick'];

        $this->store[$chan][$nick] = self::REGULAR;
    }

    /**
     * Tracks users leaving a channel
     *
     * @return void
     */
    public function onPart(Event $event)
    {
        $msg = $event->getMessage();

        if (!isset($msg['nick'])) {
            return;
        }

        $chan = strtolower($msg['params']['channels']);
        $nick = $msg['nick'];

        if (isset($this->store[$chan][$nick])) {
            unset($this->store[$chan][$nick]);
        }
    }

    /**
     * Tracks users quitting a server
     *
     * @return void
     */
    public function onQuit(Event $event)
    {
        $msg = $event->getMessage();

        if (!isset($msg['nick'])) {
            return;
        }

        $nick = $msg['nick'];

        foreach ($this->store as $chan => $store) {
            $chan = strtolower($chan);
            if (isset($store[$nick])) {
                unset($this->store[$chan][$nick]);
            }
        }
    }

    /**
     * Tracks users changing nicks
     *
     * @return void
     */
    public function onNick(Event $event)
    {
        $msg = $event->getMessage();

        if (!isset($msg['nick'])) {
            return;
        }

        $nick = $msg['nick'];
        $newNick = $msg['params']['nickname'];

        foreach ($this->store as $chan => $store) {
            $chan = strtolower($chan);
            if (isset($store[$nick])) {
                $this->store[$chan][$newNick] = $store[$nick];
                unset($this->store[$chan][$nick]);
            }
        }
    }

    /**
     * Populates the internal user listing for a channel when the bot joins it.
     *
     * @return void
     */
    public function onNameReply(Event $event)
    {
        $msg = $event->getMessage();
        $chan  = strtolower($msg['params'][3]);
        $names = explode(' ', $msg['params'][4]);

        foreach ($names as $user) {
            $flag = self::REGULAR;
            if ($user[0] == '~') {
                $flag |= self::OWNER;
            } else if ($user[0] == '&') {
                $flag |= self::ADMIN;
            } else if ($user[0] == '@') {
                $flag |= self::OP;
            } else if ($user[0] == '%') {
                $flag |= self::HALFOP;
            } else if ($user[0] == '+') {
                $flag |= self::VOICE;
            }

            if ($flag != self::REGULAR) {
                $user = substr($user, 1);
            }

            $this->store[$chan][$user] = $flag;
        }
    }

    /**
     * Debugging function
     *
     * @return void
     */
    public function onPrivmsg(Event $event)
    {
        if ($this->debug == false) {
            return;
        }

        $msg = $event->getMessage();
        $writeStream = $event->getWriteStream();

        $target = $msg['targets'][0];
        $msg = $msg['params']['text'];

        if (preg_match('#^ishere (\S+)$#', $msg, $m)) {
            $writeStream->ircPrivmsg(
                $target, $this->isIn($m[1], $target) ? 'true' : 'false'
            );
        } elseif (preg_match('#^isowner (\S+)$#', $msg, $m)) {
            $writeStream->ircPrivmsg(
                $target, $this->isOwner($m[1], $target) ? 'true' : 'false'
            );
        } elseif (preg_match('#^isadmin (\S+)$#', $msg, $m)) {
            $writeStream->ircPrivmsg(
                $target, $this->isAdmin($m[1], $target) ? 'true' : 'false'
            );
        } elseif (preg_match('#^isop (\S+)$#', $msg, $m)) {
            $writeStream->ircPrivmsg(
                $target, $this->isOp($m[1], $target) ? 'true' : 'false'
            );
        } elseif (preg_match('#^ishop (\S+)$#', $msg, $m)) {
            $writeStream->ircPrivmsg(
                $target, $this->isHalfop($m[1], $target) ? 'true' : 'false'
            );
        } elseif (preg_match('#^isvoice (\S+)$#', $msg, $m)) {
            $writeStream->ircPrivmsg(
                $target, $this->isVoice($m[1], $target) ? 'true' : 'false'
            );
        } elseif (preg_match('#^channels (\S+)$#', $msg, $m)) {
            $channels = $this->getChannels($m[1]);
            $writeStream->ircPrivmsg(
                $target, $channels ? join(', ', $channels) : 'unable to find nick'
            );
        } elseif (preg_match('#^users (\S+)$#', $msg, $m)) {
            $nicks = $this->getUsers($m[1]);
            $writeStream->ircPrivmsg(
                $target, $nicks ? join(', ', $nicks) : 'unable to find channel'
            );
        } elseif (preg_match('#^random (\S+)$#', $msg, $m)) {
            $nick = $this->getrandomuser($m[1]);
            $writeStream->ircPrivmsg($target, $nick ? $nick : 'unable to  find channel');
        }
    }

    /**
     * Checks whether or not a given user has a mode
     *
     * @param int    $mode A numeric mode (identified by the class constants)
     * @param string $nick The nick to check
     * @param string $chan The channel to check in
     *
     * @return bool
     */
    public function is($mode, $nick, $chan)
    {
        $chan = trim(strtolower($chan));
        $nick = trim($nick);

        if (!isset($this->store[$chan][$nick])) {
            return false;
        }

        return ($this->store[$chan][$nick] & $mode) != 0;
    }

    /**
     * Checks whether or not a given user has owner (~) status
     *
     * @param string $nick The nick to check
     * @param string $chan The channel to check in
     *
     * @return bool
     */
    public function isOwner($nick, $chan)
    {
        return $this->is(self::OWNER, $nick, $chan);
    }

    /**
     * Checks whether or not a given user has admin (&) status
     *
     * @param string $nick The nick to check
     * @param string $chan The channel to check in
     *
     * @return bool
     */
    public function isAdmin($nick, $chan)
    {
        return $this->is(self::ADMIN, $nick, $chan);
    }

    /**
     * Checks whether or not a given user has operator (@) status
     *
     * @param string $nick The nick to check
     * @param string $chan The channel to check in
     *
     * @return bool
     */
    public function isOp($nick, $chan)
    {
        return $this->is(self::OP, $nick, $chan);
    }

    /**
     * Checks whether or not a given user has halfop (%) status
     *
     * @param string $nick The nick to check
     * @param string $chan The channel to check in
     *
     * @return bool
     */
    public function isHalfop($nick, $chan)
    {
        return $this->is(self::HALFOP, $nick, $chan);
    }

    /**
     * Checks whether or not a given user has voice (+) status
     *
     * @param string $nick The nick to check
     * @param string $chan The channel to check in
     *
     * @return bool
     */
    public function isVoice($nick, $chan)
    {
        return $this->is(self::VOICE, $nick, $chan);
    }

    /**
     * Checks whether or not a given user is in a channel
     *
     * @param string $nick The nick to check
     * @param string $chan The channel to check in
     *
     * @return bool
     */
    public function isIn($nick, $chan)
    {
        return $this->is(self::REGULAR, $nick, $chan);
    }

    /**
     * Returns the entire user list for a channel or false if the bot is not
     * in the channel.
     *
     * @param string $chan The channel name
     *
     * @return array|bool
     */
    public function getUsers($chan)
    {
        $chan = trim(strtolower($chan));
        if (isset($this->store[$chan])) {
            return array_keys($this->store[$chan]);
        }
        return false;
    }

    /**
     * Returns the nick of a random user present in a given channel or false
     * if the bot is not present in the channel.
     *
     * To exclude the bot's current nick, for example:
     *     $chan = $this->getEvent()->getSource();
     *     $current_nick = $this->getConnection()->getNick();
     *     $random_user = $this->plugins->getPlugin('UserInfo')
     *          ->getRandomUser( $chan, array( $current_nick ) );
     *
     * @param string $chan   The channel name
     * @param array  $ignore A list of nicks to ignore in the channel.
     *                       Useful for excluding the bot itself.
     *
     * @return string|bool
     */
    public function getRandomUser($chan, $ignore = array('chanserv'))
    {
        $chan = trim(strtolower($chan));

        if (isset($this->store[$chan])) {
            do {
                $nick = array_rand($this->store[$chan], 1);
            } while (in_array($nick, $ignore));

            return $nick;
        }

        return false;
    }

    /**
     * Returns a list of channels in which a given user is present.
     *
     * @param string $nick Nick of the user
     *
     * @return array|bool
     */
    public function getChannels($nick)
    {
        $nick = trim($nick);
        $channels = array();

        foreach ($this->store as $chan => $store) {
            if (isset($store[$nick])) {
                $channels[] = $chan;
            }
        }

        return $channels;
    }
}
