<?php
namespace Phoebe\Plugin\Url;

class TimeDuration
{
    /**
     * @param int $seconds
     * @return string
     */
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

    /**
     * @param \DateInterval $interval
     * @return string
     */
    public static function format(\DateInterval $interval)
    {
        $string = $interval->format('%I:%S');

        if ($interval->h > 0) {
            $string = $interval->format('%H:').$string;
        }

        if ($interval->d > 0) {
            $string = $interval->format('%D:').$string;
        }

        return $string;
    }
}
