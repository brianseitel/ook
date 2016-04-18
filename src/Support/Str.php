<?php

namespace Support;

class Str {

    /**
     * Helper function that only replaces the first occurrence of a string.
     *
     * @param string $needle
     * @param string $replace
     * @param string $haystack
     * @return string
     */
    public static function str_replace_first($needle, $replace, $haystack) {
        $pos = strpos($haystack, $needle);
        if ($pos !== false) {
            return substr_replace($haystack, $replace, $pos, strlen($needle));
        }
        return $haystack;
    }

}
