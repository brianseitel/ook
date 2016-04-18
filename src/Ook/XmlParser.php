<?php

namespace Ook;

class XMLParser {

    /**
     * The XML object that we will parse
     * @var SimpleXMLElement
     */
    private $xml = null;

    /**
     * The options passed into the parser.
     * @var array
     */
    private $options = [];

    /**
     * The namespace separator. Usually a colon.
     * @var string
     */
    private $namespaceSeparator = ':';

    /**
     * Accepts an XML input to convert to an array. This respects namespaces, attributes,
     * and values.
     *
     * @param SimpleXmlElement $xml
     * @param array $options
     * @return void
     */
    public function __construct($xml, $options = array()) {
        $defaults = array(
            'attributePrefix'    => '@',      //to distinguish between attributes and nodes with the same name
            'alwaysArray'        => array(),  //array of xml tag names which should always become arrays
            'autoArray'          => true,     //only create arrays for tags which appear more than once
            'textContent'        => '$',      //key used for the text content of elements
            'autoText'           => true,     //skip textContent key if node has no attributes or child nodes
        );
        $this->options = array_merge($defaults, $options);

        $this->xml = $xml;
    }

    /**
     * Converts XML into an array, respecting namespaces, attributes, and text values.
     *
     * @return array
     */
    public function parse() {
        $namespaces = $this->xml->getDocNamespaces();
        $namespaces[''] = null; //add base (empty) namespace

        $attributes = $this->getAttributes($namespaces);

        //get child nodes from all namespaces
        $tags = array();
        foreach ($namespaces as $prefix => $namespace) {
            foreach ($this->xml->children($namespace) as $childXml) {
                $new_parser = new XmlParser($childXml, $this->options);
                $child      = $new_parser->parse();
                list($childTag, $childProperties) = each($child);

                //add namespace prefix, if any
                if ($prefix) {
                    $childTag = $prefix . $this->namespaceSeparator . $childTag;
                }

                if (!isset($tags[$childTag])) {
                    $alwaysArray = $this->options['alwaysArray'];
                    $autoArray   = $this->options['autoArray'];

                    $tags[$childTag] = $childProperties;
                    if (in_array($childTag, $alwaysArray) || !$autoArray) {
                        $tags[$childTag] = [$childProperties];
                    }
                } elseif ($this->isIntegerIndexedArray($tags[$childTag])) {
                    $tags[$childTag][] = $childProperties;
                } else {
                    //key exists so convert to integer indexed array with previous value in position 0
                    $tags[$childTag] = array($tags[$childTag], $childProperties);
                }
            }
        }

        //get text content of node
        $textContent = array();
        $plainText = trim((string)$this->xml);
        if ($plainText !== '') {
            $textContent[$this->options['textContent']] = $plainText;
        }

        //stick it all together
        $properties = $plainText;
        if (!$this->options['autoText'] || $attributes || $tags || ($plainText === '')) {
            $properties = array_merge($attributes, $tags, $textContent);
        }

        //return node as array
        return array(
            $this->xml->getName() => $properties
        );
    }

    /**
     * Helper function to determine whether an array is index with integers.
     * @param array $array
     * @return bool True if indexed by integers
     */
    private function isIntegerIndexedArray($array)
    {
        if (!is_array($array)) {
            return false;
        }

        $actual_keys   = array_keys($array);
        $expected_keys = range(0, count($array) - 1);

        return $actual_keys === $expected_keys;
    }

    /**
     * Retrieves the attributes for namespaces
     *
     * @param array $namespaces
     * @return array
     */
    private function getAttributes($namespaces = [])
    {
        //get attributes from all namespaces
        $attributes = array();
        foreach ($namespaces as $prefix => $namespace) {
            foreach ($this->xml->attributes($namespace) as $attributeName => $attribute) {
                //replace characters in attribute name
                $attributeKey = $this->options['attributePrefix']
                        . ($prefix ? $prefix . $this->namespaceSeparator : '')
                        . $attributeName;
                $attributes[$attributeKey] = (string)$attribute;
            }
        }
        return $attributes;
    }

}
