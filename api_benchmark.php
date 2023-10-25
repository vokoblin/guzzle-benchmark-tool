<?php

require 'vendor/autoload.php';
require 'src/ApiBenchmark.php';

$options = getopt('', ['url:', 'method:', 'body:', 'form-params:', 'headers:', 'rounds:', 'concurrency:']);

if (!isset($options['url'])) {
    die("Error: Missing required option --url.\n");
}

$url = $options['url'];
$method = isset($options['method']) ? $options['method'] : 'GET';
$body = isset($options['body']) ? $options['body'] : '';
parse_str(isset($options['form-params']) ? $options['form-params'] : '', $formParams);
$headers = isset($options['headers']) ? explode(',', $options['headers']) : [];
$rounds = isset($options['rounds']) ? (int)$options['rounds'] : 100;
$concurrency = isset($options['concurrency']) ? (int)$options['concurrency'] : 100;

$benchmark = new \GuzzleBenchmarkTool\ApiBenchmark($url, $method, $body, $formParams, $headers, $rounds, $concurrency);
$benchmark->run();
$benchmark->printResults();
$benchmark->dumpResponsesTo('responses.log');
