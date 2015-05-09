<?php
namespace Phoebe\Plugin\UserInfo;

use PDO;

class StorageSqlite implements StorageInterface
{
    protected $db;

    public function __construct()
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->exec(
            'CREATE TABLE [users] ('.
            '  [nickname] VARCHAR NOT NULL COLLATE NOCASE, '.
            '  [mode] TINYINT NOT NULL DEFAULT '.UserInfoPlugin::REGULAR.', '.
            '  [channel] VARCHAR NOT NULL COLLATE NOCASE);'.
            'CREATE INDEX [channel] ON [users] ([channel] COLLATE NOCASE);'.
            'CREATE INDEX [nickname] ON [users] ([nickname] COLLATE NOCASE);'.
            'CREATE UNIQUE INDEX [nick-chan] ON [users] ('.
            '  [nickname] COLLATE NOCASE, '.
            '  [channel] COLLATE NOCASE);'
        );
    }

    public function clear()
    {
        $this->db->exec('DELETE FROM users');
    }

    public function setUserMode($nickname, $channel, $mode)
    {
        $stmt = $this->db->prepare(
            'INSERT OR REPLACE INTO users (nickname, channel, mode) '.
            'VALUES (?, ?, ?)'
        );
        $stmt->execute([$nickname, $channel, $mode]);
    }

    public function getUserMode($nickname, $channel)
    {
        $stmt = $this->db->prepare(
            'SELECT mode FROM users '.
            'WHERE nickname = ? AND channel = ?'
        );
        $stmt->execute([$nickname, $channel]);
        return (int)$stmt->fetch(PDO::FETCH_COLUMN);
    }

    public function updateNickname($oldNickname, $newNickname)
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET nickname = ? '.
            'WHERE nickname = ?'
        );
        $stmt->execute([$newNickname, $oldNickname]);
    }

    public function removeUser($nickname, $channel = null)
    {
        if ($channel === null) {
            $stmt = $this->db->prepare(
                'DELETE FROM users WHERE nickname = ?'
            );
            $stmt->execute([$nickname]);
        } else {
            $stmt = $this->db->prepare(
                'DELETE FROM users WHERE nickname = ? AND channel = ?'
            );
            $stmt->execute([$nickname, $channel]);
        }
    }

    public function getChannels($nickname)
    {
        $stmt = $this->db->prepare(
            'SELECT channel FROM users WHERE nickname = ?'
        );
        $stmt->execute([$nickname]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getUsers($channel)
    {
        $stmt = $this->db->prepare(
            'SELECT nickname FROM users WHERE channel = ?'
        );
        $stmt->execute([$channel]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getRandomUser($channel, $ignore = [])
    {
        $stmt = $this->db->prepare(
            'SELECT nickname FROM users '.
            'WHERE channel = ? AND nickname NOT IN (?) '.
            'ORDER BY RANDOM() LIMIT 1'
        );

        if (count($ignore) > 0) {
            foreach ($ignore as $n => $nickname) {
                $ignore[$n] = $this->db->quote($nickname);
            }
            $notIn = implode(',', $ignore);
        } else {
            $notIn = '';
        }

        $stmt->execute([$channel, $notIn]);
        return $stmt->fetch(PDO::FETCH_COLUMN);
    }
}
