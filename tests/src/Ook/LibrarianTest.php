<?php

use Ook\Librarian;

class LibrarianTest extends PHPUnit_Framework_TestCase
{
    public function testGetData()
    {
        $testXml = '<items><item>Item</item></items>';
        $ook = new Librarian($testXml, '/tmp');

        $results = $ook->getData();
        $this->assertEquals(['items' => ['item' => 'Item']], $results);
    }

    public function testSetExpandSize()
    {
        $testXml = '<items><item>Item</item></items>';
        $ook = new Librarian($testXml, '/tmp');
        $ook->setExpandSize(5);

        $this->assertEquals(5, $ook->expand_size);
    }

    public function testSetParserOptions()
    {
        $testOptions = ['alwaysArray' => ['foo']];
        $testXml = '<items><item>Item</item></items>';
        $ook = new Librarian($testXml, '/tmp');
        $ook->setParserOptions($testOptions);

        $this->assertEquals($testOptions, $ook->parser_options);
    }

    public function testHandleInputXMLString()
    {
        $testXml = '<items><item>Item</item></items>';
        $ook = new Librarian($testXml, '/tmp');

        $this->assertEquals(['items' => ['item' => 'Item']], $ook->getData());
    }

    public function testHandleInputXMLObject()
    {
        $testXml = '<items><item>Item</item></items>';

        $testSimpleXmlObject = simplexml_load_string($testXml);
        $ook = new Librarian($testSimpleXmlObject, '/tmp');

        $this->assertEquals(['items' => ['item' => 'Item']], $ook->getData());
    }

    public function testHandleInputXMLFile()
    {
        $testXml = '<?xml version="1.0" encoding="UTF-8"?><items><item>Item</item></items>';
        file_put_contents('/tmp/test_file.xml', $testXml);
        $ook = new Librarian('/tmp/test_file.xml', '/tmp');

        $this->assertEquals(['items' => ['item' => 'Item']], $ook->getData());
        unlink('/tmp/test_file.xml');
    }

    public function testHandleInputJSON()
    {
        $testJson = '{"items":{"item":"Item"}}';
        $ook = new Librarian($testJson, '/tmp');

        $this->assertEquals(['items' => ['item' => 'Item']], $ook->getData());
    }

    public function testHandleInputJSONFile()
    {
        $testJson = '{"items":{"item":"Item"}}';
        file_put_contents('/tmp/test_file.json', $testJson);
        $ook = new Librarian('/tmp/test_file.json', '/tmp');

        $this->assertEquals(['items' => ['item' => 'Item']], $ook->getData());
        unlink('/tmp/test_file.json');
    }

    public function testHandleInputInvalidFileType()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('File type not allowed.');

        file_put_contents('/tmp/test_file.pdf', 'foo');
        $ook = new Librarian('/tmp/test_file.pdf', '/tmp');


        unlink('/tmp/test_file.pdf');
    }

    public function testHandleInputUnknownType()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Ook doesn\'t know what to do with this type of input.');

        $ook = new Librarian('/tmp/not_a_real_file', '/tmp');
    }

    public function testLoadConfigBadFile()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('File does not exist at path /tmp/fake_config_file');

        $testXml = '<?xml version="1.0" encoding="UTF-8"?><items><item>Item</item></items>';
        file_put_contents('/tmp/temp.xml', $testXml);
        $ook = new Librarian('/tmp/temp.xml', '/tmp/fake_config_file');

        unlink('/tmp/temp.xml');
    }

    public function testTransform()
    {
        $testXml = '<?xml version="1.0" encoding="UTF-8"?><items><item>Item</item></items>';
        $config = 'foo.bar: items.item';
        file_put_contents('/tmp/temp_config.yaml', $config);
        $ook = new Librarian($testXml, '/tmp/temp_config.yaml');

        $results = $ook->transform();

        $this->assertEquals('Item', $results['foo']['bar']);

        unlink('/tmp/temp_config.yaml');
    }

    public function testTransformWithWildcards()
    {
        $testXml = '<?xml version="1.0" encoding="UTF-8"?><items><item>Item</item><item>Another Item</item></items>';
        $config = 'foo.bar.*: items.item.*';
        file_put_contents('/tmp/temp_config.yaml', $config);
        $ook = new Librarian($testXml, '/tmp/temp_config.yaml');

        $results = $ook->transform();

        $this->assertEquals('Item', $results['foo']['bar'][0]);
        $this->assertEquals('Another Item', $results['foo']['bar'][1]);

        unlink('/tmp/temp_config.yaml');
    }
}