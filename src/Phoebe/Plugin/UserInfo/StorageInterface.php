<?php
namespace Phoebe\Plugin\UserInfo;

interface StorageInterface
{
    public function clear();
    public function setUserMode($nickname, $channel, $mode);
    public function getUserMode($nickname, $channel);
    public function updateNickname($oldNickname, $newNickname);
    public function removeUser($nickname, $channel = null);
    public function getChannels($nickname);
    public function getUsers($channel);
    public function getRandomUser($channel, $ignore = []);
}
