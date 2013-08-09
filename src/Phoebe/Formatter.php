<?php
namespace gamesrv;

class Formatter
{
    protected static $formatting = array(
        'clear'      => "\x0f",
        'bold'       => "\x02",
        'underline'  => "\x1f",
        'reverse'    => "\x16",
        'italic'     => "\x1d",
        'fixed'      => "\x11",
        'blink'      => "\x06"
    );
    
    protected static $colors = array(
        'white'   => '00',
        'black'   => '01',
        'navy'    => '02',
        'green'   => '03',
        'red'     => '04',
        'brown'   => '05',
        'purple'  => '06',
        'orange'  => '07',
        'yellow'  => '08',
        'lime'    => '09',
        'teal'    => '10',
        'aqua'    => '11',
        'blue'    => '12',
        'pink'    => '13',
        'gray'    => '14',
        'silver'  => '15'
    );
    
    public static function color($text, $foreground, $background = null)
    {
        $foreground = self::$colors[$foreground];
        $code = "\x03".$foreground;
        
        if ($background !== null) {
            $background = self::$colors[$background];
            $code .= ','.$background;
        }
        
        return $code.$text."\x03";
    }
    
    public static function stripFormatting($str)
    {
        return preg_replace(
            '/\x1f|\x02|\x12|\x0f|\x16|\x03(?:\d{1,2}(?:,\d{1,2})?)?/',
            '',
            $str
        );
    }
    
    public static function format($type, $msg)
    {
        if (isset(self::$formatting[$type])) {
            $code = self::$formatting[$type];
            return $code.$msg.$code;
        } else {
            return $msg;
        }
    }
    
    public static function __callStatic($name, $args)
    {
        return self::format($name, $args[0]);
    }
}
