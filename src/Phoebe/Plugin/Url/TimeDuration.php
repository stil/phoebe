<?php
namespace Phoebe\Plugin\Url;

class TimeDuration
{
    public static function get($seconds)
    {
        $hours   = intval($seconds/3600);
        $minutes = intval(($seconds/60)%60);
        $seconds = intval($seconds%60);
        $result  = '';
        if ($hours > 0) {
            $result .= $hours.':';
        }
        $result .= str_pad($minutes, 2, '0', STR_PAD_LEFT).':';
        $result .= str_pad($seconds, 2, '0', STR_PAD_LEFT);
        return $result;
    }
}
