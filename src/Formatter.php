<?php
namespace Phoebe;

use Exception;

class Formatter
{
    protected static $formatting = [
        'clear'      => "\x0f",
        'bold'       => "\x02",
        'underline'  => "\x1f",
        'reverse'    => "\x16",
        'italic'     => "\x1d",
        'fixed'      => "\x11",
        'blink'      => "\x06"
    ];

    protected static $tags = [
        'b' => 'bold',
        'u' => 'underline',
        'i' => 'italic',
        'clear' => 'clear'
    ];

    protected static $colors = [
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
    ];
    
    /**
     * Applies colors to the text
     * @param  string $text       Text to format
     * @param  string $foreground Foreground color
     * @param  string $background Background color (optional)
     * @return string             Coloured text
     */
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
    
    /**
     * Strips all formatting from IRC message
     * @param  string $str Formatted text
     * @return string      Clear, unformatted text
     */
    public static function stripFormatting($str)
    {
        return preg_replace(
            '/\x1f|\x02|\x12|\x0f|\x16|\x03(?:\d{1,2}(?:,\d{1,2})?)?/',
            '',
            $str
        );
    }
    
    protected static function format($type, $msg)
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

    /**
     * Parses and returns IRC formatted text
     * @param  string $msg Human readable, formatted text.
     *                     Available tags: <b> </b> (bold)
     *                                     <i> </i> (italics)
     *                                     <u> </u> (underline)
     *                                     <color fg="red" bg="blue"> </color> (colored text, bg is optional)
     *                                     <clear> (clears all previous formatting)
     * @return string      Proper IRC formatted text
     */
    public static function parse($msg)
    {
        $msg = preg_replace_callback(
            '/<(\/)?(.+?)(?: (.+?))?>/',
            function ($matches) {
                $tag = $matches[2];
                $closingTag = $matches[1] == '/';
                $attributes = isset($matches[3]) ? $matches[3] : null;

                if (isset(self::$tags[$tag])) {
                    return self::$formatting[self::$tags[$tag]];
                } else if ($tag == 'color') {
                    if ($closingTag) {
                        return "\x03";
                    } else if (!$closingTag && !$attributes) {
                        throw new Exception('<color> has no attributes set.');
                    }

                    $colors = [];
                    if (0 === preg_match('/fg=["|\'](.+?)["|\'](?: bg=["|\'](.+?)["|\'])?/', $attributes, $colors)) {
                        throw new Exception('<color> has invalid attributes set.');
                    }

                    $fg = $colors[1];
                    if (isset(self::$colors[$fg])) {
                        $fg = self::$colors[$fg];
                    } else {
                        throw new Exception('Invalid foreground color "'.$fg.'".');
                    }

                    if (isset($colors[2])) {
                        $bg = $colors[2];
                        if (isset(self::$colors[$bg])) {
                            $bg = ','.self::$colors[$bg];
                        } else {
                            throw new Exception('Invalid background color "'.$bg.'".');
                        }
                    } else {
                        $bg = '';
                    }

                    return "\x03".$fg.$bg;
                } else {
                    return null;
                }
            },
            $msg
        );

        return $msg;
    }
}
