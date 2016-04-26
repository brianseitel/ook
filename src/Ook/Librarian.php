<?php

namespace Ook;

use Exception;
use Support\Arr;
use SimpleXMLElement;
use Symfony\Component\Yaml\Parser;

class Librarian {

    /**
     * The file that we wish to translate
     * @var string
     */
    private $file;

    /**
     * The data that we converted into an array after loading from the file.
     * @var array
     */
    public $feed_data;

    /**
     * The configuration passed in via YAML file
     * @var array
     */
    private $rules;

    /**
     * Maximum size to expand a list of elements in a config.
     *
     * @var string
     */
    public $expand_size = 1000;

    /**
     * The options to pass into the parser.
     *
     * @var array
     */
    public $parser_options = [];

    /**
     * Loads the input file and the configuration / rule set
     * @param mixed $input Data to parse. Could be XML string, JSON string, file path, or SimpleXMLElement
     * @param string $config_path
     * @return void
     */
    public function __construct($xml, $config_path)
    {
        $this->handleInput($xml);
        $this->loadConfig($config_path);
    }

    public function getData()
    {
        return $this->feed_data;
    }

    /**
     * Sets the maximum size for a list of elements in a config.
     *
     * Ex: items.*.url would turn into [items.0.url ... items.999.url]
     *
     * @param int $size
     * @return void
     */
    public function setExpandSize($size)
    {
        $this->expand_size = $size;
    }

    /**
     * Sets parser options for the XMLParser
     *
     * @param array $options
     * @return void
     */
    public function setParserOptions($options = [])
    {
        $this->parser_options = $options;
    }

    /**
     * Handles the input for a given input type. It examines
     * the class type, the extension, or attempts a conversion
     * to determine the input type. Once determined, it sends
     * it to the appropriate loader.
     *
     * @param mixed $input
     * @return array
     */
    public function handleInput($input)
    {
        if ((is_object($input) && get_class($input) == 'SimpleXMLElement')) {
            return $this->loadSimpleXML($input);
        }

        if (is_string($input)) {
            if (file_exists($input)) {
                if (strpos($input, '.xml')) {
                    return $this->loadFileXML($input);
                } elseif (strpos($input, '.json')) {
                    return $this->loadFileJSON($input);
                } else {
                    throw new Exception('File type not allowed.');
                }
            }

            if ($json = json_decode($input, 1)) {
                return $this->loadJSON($input);
            }

            try {
              if ($xml = simplexml_load_string($input)) {
                  return $this->loadXML($xml);
              }
            } catch (Exception $e) {
              // don't do anything if this fails.
            }
        }
        throw new Exception('Ook doesn\'t know what to do with this type of input.');
    }

    /**
     * Loads an XML file then sends it to the parser.
     *
     * @param string $path
     * @return void
     */
    public function loadFileXML($path) {
        $xml = simplexml_load_file($path);
        $this->parseXml($xml);
    }

    /**
     * Loads a JSON file, then decodes it.
     *
     * @param string $path
     * @return void
     */
    public function loadFileJSON($path) {
        $file = file_get_contents($path);
        $this->feed_data = json_decode($file, 1);
    }

    /**
     * Loads a SimpleXML object and parses it.
     *
     * @param SimpleXMLElement $input
     * @return void
     */
    public function loadSimpleXML(SimpleXMLElement $input) {
        $this->parseXml($input);
    }

    /**
     * Loads a JSON string and parses it.
     *
     * @param string $json
     * @return void
     */
    public function loadJSON($json) {
        $this->feed_data = json_decode($json, 1);
    }

    /**
     * Loads an XML string and parses it
     *
     * @param string $xml
     * @return void
     */
    public function loadXML($xml) {
        $this->parseXml($xml);
    }

    /**
     * Begins parsing a SimpleXML object
     *
     * @param SimpleXMLElement $xml
     * @return void
     */
    private function parseXml(SimpleXMLElement $xml)
    {
        $parser = new XmlParser($xml, $this->parser_options);
        $this->feed_data = $parser->parse();
    }
    /**
     * Retrieves the rule set from the configuration file, then parses
     * it from YAML into an array.
     *
     * @param string $config_path
     * @return void
     */
    public function loadConfig($config_path)
    {
        if (!file_exists($config_path)) {
            throw new Exception('File does not exist at path ' . $config_path);
        }

        $file = file_get_contents($config_path);

        $parser = new Parser;
        $this->rules = $parser->parse($file);
    }

    /**
     * Transforms the input array into the output array based on the rules
     * stated in the configuration file.
     *
     * @return array
     */
    public function transform($root = '') {
        $output = [];

        $array = Arr::dot($this->feed_data);
        $rules = Arr::dot($this->rules);

        $all_rules = [];
        foreach ($rules as $key => $value) {
            if (strpos($key, '*')) {
                $new_keys = Arr::expandKeys($key, $this->expand_size);
                $new_values = Arr::expandKeys($value, $this->expand_size);

                $new_rules = array_combine($new_keys, $new_values);
                foreach ($new_rules as $new_key => $new_rule) {
                    if (array_key_exists($new_rule, $array)) {
                        $all_rules[$new_key] = $new_rule;
                    }
                }
            } else {
                $all_rules[$key] = $value;
            }
        }

        foreach ($all_rules as $k => $v) {
            Arr::set($output, $k, Arr::get($array, $v));
        }

        return $output;
    }

}

