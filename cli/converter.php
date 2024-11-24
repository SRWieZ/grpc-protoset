#!/usr/bin/env php
<?php

use SRWieZ\GrpcProtoset\ProtosetConverter;

include $_composer_autoload_path ?? __DIR__.'/../vendor/autoload.php';

$filename = $argv[1] ?? null;
if (! $filename) {
    echo 'Usage: '.$argv[0].' <filename.protoset> [output]'.PHP_EOL;
    exit(1);
}

if (! file_exists($filename)) {
    echo 'File not found: '.$filename.PHP_EOL;
    exit(1);
}

$output = $argv[2] ?? null;

// Check if parent directory is writable
if ($output && ! is_writable(dirname($output))) {
    echo 'Output directory is not writable: '.dirname($output).PHP_EOL;
    exit(1);
}

// Check if output directory exists && is not empty
if ($output && is_dir($output) && count((array) glob($output.'/*'))) {
    echo 'Output directory is not empty: '.$output.PHP_EOL;
    exit(1);
}

$converter = new ProtosetConverter;

if ($output) {
    $converter->setOutputDir($output);
}

try {
    $converter->convertProtoset($filename);
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage().PHP_EOL;
    exit(1);
}
