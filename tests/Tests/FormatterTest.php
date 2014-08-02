<?php
namespace Phoebe\Tests;

use Phoebe\Formatter;
use Exception;

class FormatterTest extends \PHPUnit_Framework_TestCase
{
    protected function assertValidFormat($after, $before)
    {
        return $this->assertEquals($after, Formatter::parse($before));
    }

    public function testEmphasize()
    {
        $this->assertValidFormat(
            "Lorem \x02ipsum\x02 dolor \x1dsit amet\x1d, consectetur adipiscing elit.",
            'Lorem <b>ipsum</b> dolor <i>sit amet</i>, <x>consectetur</x> adipiscing elit.'
        );

        $this->assertEquals(
            "Foo \x02bar\x02",
            'Foo '.Formatter::bold('bar')
        );
    }

    public function testColoring()
    {
        $this->assertValidFormat(
            "Foo\x03 bar",
            'Foo</color> bar'
        );

        $this->assertValidFormat(
            "Foo\x0304,12 bar",
            'Foo<color fg="red" bg="blue"> bar'
        );

        $this->assertValidFormat(
            "Foo\x0304,12 bar",
            "Foo<color fg='red' bg='blue'> bar"
        );

        $this->assertValidFormat(
            "Foo\x0310 bar",
            'Foo<color fg="teal"> bar'
        );
    }
}
