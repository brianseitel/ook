<?php

use Ook\XMLParser;

class XMLParserTest extends PHPUnit_Framework_TestCase
{
    public function testParse()
    {
        $testXml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><items><item>Item</item></items>');
        $parser = new XmlParser($testXml);
        $results = $parser->parse();

        $this->assertEquals('Item', $results['items']['item']);
    }

    public function testParseWithAttributes()
    {
        $testXml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><items><item src="foo">Item</item></items>');
        $parser = new XmlParser($testXml);
        $results = $parser->parse();

        $this->assertEquals('foo', $results['items']['item']['@src']);
        $this->assertEquals('Item', $results['items']['item']['$']);
    }

    public function testParseWithNamespaces()
    {
        $testXml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
        	<items xmlns:m="http://teststuff.com/foo">
        		<m:item src="foo">Item</m:item>
        	</items>');
        $parser = new XmlParser($testXml);
        $results = $parser->parse();

        $this->assertEquals('foo', $results['items']['m:item']['@src']);
        $this->assertEquals('Item', $results['items']['m:item']['$']);
    }

    public function testParseWithIntegerIndexedArray()
    {
        $testXml = simplexml_load_file('./tests/raw/test_feed.xml');
        $parser = new XmlParser($testXml);
        $results = $parser->parse();

        $this->assertTrue(isset($results['rss']['channel']));
    }


}