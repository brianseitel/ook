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

    /**
     * Loads the input file and the configuration / rule set
     * @param string $file_path
     * @param string $config_path
     * @return void
     */
    public function __construct($file_path, $config_path)
    {
        $this->loadFile($file_path);
        $this->loadConfig($config_path);
    }

    /**
     * Retrieves the data from the input file path, if it exists,
     * then parses it based on the file extension.
     *
     * @param string $file_path
     * @return void
     */
    public function loadFile($file_path)
    {
        if (!file_exists($file_path)) {
            throw new Exception('File does not exist at path ' . $file_path);
        }

        if (strpos($file_path, '.json') !== false) {
            $this->feed_data = json_decode(file_get_contents($file_path), 1);
        } else if (strpos($file_path, '.xml') !== false) {
            $xml = simplexml_load_file($file_path);
            $parser = new XmlParser($xml);
            $this->feed_data = $parser->parse();
        } else {
            throw new Exception('File type not supported.');
        }
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
            throw new Exception('File does not exist at path ' . $file_path);
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
                $new_keys = $this->expand_keys($key);
                $new_values = $this->expand_keys($value);

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

    /**
     * Accepts a dot-notation array key with wildcards and expands them
     * into an array of dot-notation array keys in numerical order.
     *
     * @param string $key - dot notation
     * @param int $size - number of keys to generate per wildcard. Defaults to 10.
     * @return array
     */
    public function expand_keys($key, $size = 10) {
        $keys = [];
        $original_key = $key;
        for ($i = 0; $i < $size; $i++) {
            $k = $this->str_replace_first('*', $i, $original_key);
            if (strpos($k, '*') !== false) {
                $sub_keys = $this->expand_keys($k);
                foreach ($sub_keys as $sk) {
                    $keys[] = $sk;
                }
            } else {
                $keys[] = $k;
            }
        }

        return $keys;
    }

    /**
     * Helper function that only replaces the first occurrence of a string.
     *
     * @param string $needle
     * @param string $replace
     * @param string $haystack
     * @return string
     */
    public function str_replace_first($needle, $replace, $haystack) {
        $pos = strpos($haystack, $needle);
        if ($pos !== false) {
            return substr_replace($haystack, $replace, $pos, strlen($needle));
        }
        return $haystack;
    }

}
