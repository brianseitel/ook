<?php

require('../vendor/autoload.php');

$feed_path = __DIR__.'/feeds/sample.xml';
$config_path = __DIR__.'/config/sample.yaml';

$ook = new Ook\Librarian($feed_path, $config_path);
$results = $ook->transform();

print_r($results);
die();


function pp($a) { print_r($a); }
function pd($a) { pp($a); die(); }
