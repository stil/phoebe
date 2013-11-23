<?php
namespace Phoebe\Plugin\Whois;

class ProcessedUser
{
    protected $info = [];
    protected $id;
    protected $callback;

    public function __construct($nickname, $callback)
    {
        $this->id = strtolower($nickname);
        $this->callback = $callback;
    }

    public function getId()
    {
        return $this->id;
    }

    public function triggerCallback()
    {
        $callback = $this->callback;
        $callback($this->info);
    }

    public function addWhoisReply($msg)
    {
        $this->info[$msg['command']] = $msg;
    }
}
