<?php

use Support\Str;

class SupportStrTest extends PHPUnit_Framework_TestCase
{

    public function testStrReplaceFirst()
    {
        $key = 'foo.*.bar.*';
        $results = Str::strReplaceFirst('*', '0', $key);

        $this->assertEquals('foo.0.bar.*', $results);

        $results = Str::strReplaceFirst('no', '0', $results);
        $this->assertEquals('foo.0.bar.*', $results);
    }

}