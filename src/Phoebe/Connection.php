<?php
namespace Phoebe;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Phergie\Irc\ConnectionInterface;

/**
 * Uses code written by: Phergie Development Team (http://phergie.org)
 */

class Connection extends EventDispatcher implements ConnectionInterface
{
    /**
     * Server hostname
     *
     * @var string
     */
    protected $serverHostname;

    /**
     * Server port
     *
     * @var int
     */
    protected $serverPort = 6667;

    /**
     * Connection password
     *
     * @var string
     */
    protected $password;

    /**
     * User's nickname
     *
     * @var string
     */
    protected $nickname;

    /**
     * User's username
     *
     * @var string
     */
    protected $username;

    /**
     * User's hostname under RFC 1459 or the mode under RFC 2812
     *
     * @var string
     */
    protected $hostname = '0';

    /**
     * User's server name under RFC 1459 or an unused parameter under RFC 2812
     *
     * @var string
     */
    protected $servername = '*';

    /**
     * User's real name
     *
     * @var string
     */
    protected $realname;

    /**
     * Implementation-specific connection options
     *
     * @var array
     */
    protected $options = array();

    /**
     * Implements ConnectionInterface::setServerHostname().
     *
     * @param string $hostname
     * @see \Phergie\Irc\ConnectionInterface::setServerHostname()
     */
    public function setServerHostname($hostname)
    {
        $this->serverHostname = $hostname;
    }

    /**
     * Implements ConnectionInterface::getServerHostname().
     *
     * @return string
     * @see \Phergie\Irc\ConnectionInterface::getServerHostname()
     */
    public function getServerHostname()
    {
        return $this->serverHostname;
    }

    /**
     * Implements ConnectionInterface::setServerPort().
     *
     * @param int $port
     * @see \Phergie\Irc\ConnectionInterface::setServerPort()
     */
    public function setServerPort($port)
    {
        $this->serverPort = $port;
    }

    /**
     * Implements ConnectionInterface::getServerPort().
     *
     * @return int
     * @see \Phergie\Irc\ConnectionInterface::getServerPort()
     */
    public function getServerPort()
    {
        return $this->serverPort;
    }

    /**
     * Implements ConnectionInterface::setPassword().
     *
     * @param string $password
     * @see \Phergie\Irc\ConnectionInterface::setPassword()
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * Implements ConnectionInterface::getPassword().
     *
     * @return string
     * @see \Phergie\Irc\ConnectionInterface::getPassword()
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Implements ConnectionInterface::setNickname().
     *
     * @param string $nickname
     * @see \Phergie\Irc\ConnectionInterface::setNickname()
     */
    public function setNickname($nickname)
    {
        $this->nickname = $nickname;
    }

    /**
     * Implements ConnectionInterface::getNickname().
     *
     * @return string
     * @see \Phergie\Irc\ConnectionInterface::getNickname()
     */
    public function getNickname()
    {
        return $this->nickname;
    }

    /**
     * Implements ConnectionInterface::setUsername().
     *
     * @param string $username
     * @see \Phergie\Irc\ConnectionInterface::setUsername()
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * Implements ConnectionInterface::getUsername().
     *
     * @return string
     * @see \Phergie\Irc\ConnectionInterface::getUsername()
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Implements ConnectionInterface::setHostname().
     *
     * @param string $hostname
     * @see \Phergie\Irc\ConnectionInterface::setHostname()
     */
    public function setHostname($hostname)
    {
        $this->hostname = $hostname;
    }

    /**
     * Implements ConnectionInterface::getHostname().
     *
     * @return string
     * @see \Phergie\Irc\ConnectionInterface::getHostname()
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * Implements ConnectionInterface::setServername().
     *
     * @param string $servername
     * @see \Phergie\Irc\ConnectionInterface::setServername()
     */
    public function setServername($servername)
    {
        $this->servername = $servername;
    }

    /**
     * Implements ConnectionInterface::getServername().
     *
     * @return string
     * @see \Phergie\Irc\ConnectionInterface::getServername()
     */
    public function getServername()
    {
        return $this->servername;
    }

    /**
     * Implements ConnectionInterface::setRealname().
     *
     * @param string $realname
     * @see \Phergie\Irc\ConnectionInterface::setRealname()
     */
    public function setRealname($realname)
    {
        $this->realname = $realname;
    }

    /**
     * Implements ConnectionInterface::getRealname().
     *
     * @return string
     * @see \Phergie\Irc\ConnectionInterface::getRealname()
     */
    public function getRealname()
    {
        return $this->realname;
    }

    /**
     * Implements ConnectionInterface::setOption().
     *
     * @param string $name Option name
     * @param mixed $value Option value
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
    }

    /**
     * Implements ConnectionInterface::getOption().
     *
     * @param string $name Option name
     * @return mixed
     */
    public function getOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }
}
