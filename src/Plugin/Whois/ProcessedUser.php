<?php
namespace Phoebe\Plugin\Whois;

class ProcessedUser
{
    protected $info = [];
    protected $nickname;
    protected $callback;

    public function __construct($nickname, $callback)
    {
        $this->nickname = $nickname;
        $this->callback = $callback;
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
