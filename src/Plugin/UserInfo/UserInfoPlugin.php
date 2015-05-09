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

    protected static $modesNum = [
        'q' => self::OWNER,
        'a' => self::ADMIN,
        'o' => self::OP,
        'h' => self::HALFOP,
        'v' => self::VOICE
    ];

    protected static $modesPrefix = [
        '~' => 'q',
        '&' => 'a',
        '@' => 'o',
        '%' => 'h',
        '+' => 'v'
    ];

    public static function getSubscribedEvents()
    {
        return [
            'irc.received.001'     => ['onWelcome'],
            'irc.received.MODE'    => ['onMode'],
            'irc.received.JOIN'    => ['onJoin'],
            'irc.received.PART'    => ['onPart'],
            'irc.received.QUIT'    => ['onQuit'],
            'irc.received.NICK'    => ['onNick'],
            'irc.received.353'     => ['onNameReply'],
            'irc.received.PRIVMSG' => ['onPrivmsg']
        ];
    }

    /**
     * Debug mode: TRUE or FALSE
     * 
     * @var boolean
     */
    protected $debug = false;

    /**
     * An object handling all the user information on different channels
     *
     * @var StorageInterface
     */
    protected $storage;

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
     * @param StorageInterface $storage
     */
    public function __construct(StorageInterface $storage = null)
    {
        if ($storage !== null) {
            $this->storage = $storage;
        } else {
            $this->storage = new StorageSqlite();
        }
        
    }

    /**
     * Clears storage
     */
    public function onWelcome()
    {
        $this->storage->clear();
    }

    /**
     * Tracks user mode changes
     *
     * @param  Event  $event Event object
     */
    public function onMode(Event $event)
    {
        $msg = $event->getMessage();
        $params = $msg['params'];

        $isValidMessage =
            isset($params['channel'], $params['params']) &&
            preg_match('/(?:\+|-)[hovaq+-]+/i', $params['mode'])
        ;

        if (!$isValidMessage) {
            return;
        }

        $chan = $params['channel'];
        $nicks = explode(' ', $params['params']);
        $modes = str_split($params['mode'], 1);

        $operation = array_shift($modes); // + or -
        while ($char = array_shift($modes)) {
            $nick = array_shift($nicks);
            $mode = self::$modesNum[$char];

            $userMode = $this->storage->getUserMode($nick, $chan);
            if (!$userMode) {
                $userMode = self::REGULAR;
            }

            switch ($operation) {
                case '+':
                    $userMode |= $mode;
                    break;
                case '-':
                    $userMode ^= $mode;
                    break;
            }

            $this->storage->setUserMode($nick, $chan, $userMode);
        }
    }

    /**
     * Tracks users joining a channel
     *
     * @param  Event  $event Event object
     */
    public function onJoin(Event $event)
    {
        $msg = $event->getMessage();

        if (!isset($msg['nick'])) {
            return;
        }

        $chan = $msg['params']['channels'];
        $nick = $msg['nick'];

        $this->storage->setUserMode($nick, $chan, self::REGULAR);
    }

    /**
     * Tracks users leaving a channel
     *
     * @param  Event  $event Event object
     */
    public function onPart(Event $event)
    {
        $msg = $event->getMessage();

        if (!isset($msg['nick'])) {
            return;
        }

        $nick = $msg['nick'];
        $chan = $msg['params']['channels'];

        $this->storage->removeUser($nick, $chan);
    }

    /**
     * Tracks users quitting a server
     *
     * @param  Event  $event Event object
     */
    public function onQuit(Event $event)
    {
        $msg = $event->getMessage();

        if (!isset($msg['nick'])) {
            return;
        }

        $nick = $msg['nick'];

        $this->storage->removeUser($nick);
    }

    /**
     * Tracks users changing nicks
     *
     * @param  Event  $event Event object
     */
    public function onNick(Event $event)
    {
        $msg = $event->getMessage();

        $oldNick = $msg['nick'];
        $newNick = $msg['params']['nickname'];

        $this->storage->updateNickname($oldNick, $newNick);
    }

    /**
     * Populates the internal user listing for a channel when the bot joins it.
     *
     * @param  Event  $event Event object
     */
    public function onNameReply(Event $event)
    {
        $msg = $event->getMessage();
        $chan = $msg['params'][2];
        $names = explode(' ', $msg['params'][3]);

        foreach ($names as $nick) {
            $mode = self::REGULAR;
            $prefix = $nick[0];

            if (isset(self::$modesPrefix[$prefix])) {
                $mode |= self::$modesNum[self::$modesPrefix[$prefix]];
                $nick = substr($nick, 1);
            }

            $this->storage->setUserMode($nick, $chan, $mode);
        }
    }

    /**
     * Debugging function
     *
     * @param  Event  $event Event object
     */
    public function onPrivmsg(Event $event)
    {
        if ($this->debug === false) {
            return;
        }

        $msg = $event->getMessage();
        $target = $msg['targets'][0];
        $msg = $msg['params']['text'];

        $send = function ($msg) use ($event, $target) {
            $writeStream = $event->getWriteStream();
            $writeStream->ircPrivmsg($target, $msg);
        };

        $m = [];
        $match = function ($pattern, &$m) use ($msg) {
            return preg_match($pattern, $msg, $m);
        };

        if ($match('#^ishere (\S+)$#', $m)) {
            $send($this->isIn($m[1], $target) ? 'true' : 'false');
        } elseif ($match('#^users (\S+)$#', $m)) {
            $chan = $m[1];
            $nicks = $this->getUsers($chan);
            foreach ($nicks as &$nick) {
                $prefix = '';
                foreach (self::$modesNum as $mode => $modeNum) {
                    if ($this->is($modeNum, $nick, $chan)) {
                        $prefix .= array_search($mode, self::$modesPrefix);
                    }
                }
                $nick = $prefix.$nick;
            }
            $send($nicks ? join(', ', $nicks) : 'unable to find channel');
        } elseif ($match('#^channels (\S+)$#', $m)) {
            $channels = $this->getChannels($m[1]);
            $send($channels ? join(', ', $channels) : 'unable to find nick');
        } elseif ($match('#^users (\S+)$#', $m)) {
            $nicks = $this->getUsers($m[1]);
            $send($nicks ? join(', ', $nicks) : 'unable to find channel');
        } elseif ($match('#^random (\S+)$#', $m)) {
            $nick = $this->getRandomUser($m[1]);
            $send($nick ? $nick : 'unable to find channel');
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
        $userMode = $this->storage->getUserMode($nick, $chan);

        if ($userMode) {
            return ($userMode & $mode) != 0;
        } else {
            return false;
        }
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
     * Checks whether or not a given user has admin (&) mode
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
     * Checks whether or not a given user has operator (@) mode
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
     * Checks whether or not a given user has halfop (%) mode
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
     * Checks whether or not a given user has voice (+) mode
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
        return $this->storage->getUsers($chan);
    }

    /**
     * Returns the nick of a random user present in a given channel or false
     * if the bot is not present in the channel.
     *
     * @param string $chan   The channel name
     * @param array  $ignore A list of nicks to ignore in the channel.
     *                       Useful for excluding the bot itself.
     *
     * @return string|bool
     */
    public function getRandomUser($chan, $ignore = ['chanserv'])
    {
        return $this->storage->getRandomUser($chan, $ignore);
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
        return $this->storage->getChannels($nick);
    }
}
