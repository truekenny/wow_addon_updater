<?php

define('SCRIPT_DIR', dirname($_SERVER['SCRIPT_FILENAME']));
define('VERSION_FILE', SCRIPT_DIR . DIRECTORY_SEPARATOR . 'version.txt');

$version = trim(file_get_contents(VERSION_FILE));
$version = explode('.', $version);
$version[count($version) - 1]++;
$version = implode('.', $version);
file_put_contents(VERSION_FILE, $version);

echo "version up: $version\n";

system('git add ' . VERSION_FILE);
