<?php

namespace Ook;

use Exception;
use Support\Arr;
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
    private $feed_data;

    /**
     * The configuration passed in via YAML file
     * @var array
     */
    private $rules;

    private $expand_size = 1000;

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

    public function setExpandSize($size)
    {
        $this->expand_size = $size;
    }

    public function handleInput($input)
    {
        if (is_object($input) && get_class($input) == 'SimpleXMLElement') {
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
                return $this->loadJSON($json);
            }

            if ($xml = simplexml_load_string($input)) {
                return $this->loadXML($xml);
            }
        }

        throw new Exception('Ook doesn\'t know what to do with this type of input.');
    }

    public function loadFileXML($input) {
        $xml = simplexml_load_file($input);
        $parser = new XmlParser($xml);
        $this->feed_data = $parser->parse();
    }

    public function loadFileJSON($input) {
        $file = file_get_contents($input);
        $this->feed_data = json_decode($file, 1);
    }

    public function loadSimpleXML($input) {
        $parser = new XmlParser($input);
        $this->feed_data = $parser->parse();
    }

    public function loadJSON($input) {
        $this->feed_data = json_decode($input, 1);
    }

    public function loadXML($input) {
        $this->feed_data = simplexml_load_string($xml);
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
    public function transform() {
        $output = [];

        $array = Arr::dot($this->feed_data);

        $rules = $this->rules;

        $all_rules = [];
        foreach ($rules as $key => $value) {
            if (strpos($key, '*')) {
                $new_keys = Arr::expand_keys($key, $this->expand_size);
                $new_values = Arr::expand_keys($value, $this->expand_size);

                $new_rules = array_combine($new_keys, $new_values);
                foreach ($new_rules as $new_key => $new_rule) {
                    if (array_key_exists($new_rule, $array)) {
                        $all_rules[$new_key] = $new_rule;
                    }
                }
            } else {
                $all_rules[$k] = $v;
            }
        }

        foreach ($all_rules as $k => $v) {
            Arr::set($output, $k, Arr::get($array, $v));
        }

        return $output;
    }

}
