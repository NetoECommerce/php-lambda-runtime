<?php

// require autoloader first
$files = [
    getcwd() . '/vendor/autoload.php',
    __DIR__ . '/../../../autoload.php', // composer dependency
    __DIR__ . '/../vendor/autoload.php', // stand-alone package
];
foreach ($files as $file) {
    if (is_file($file)) {
        require_once $file;
        break;
    }
}

// require the user's app definition
$found = false;
for ($i = 0; $i < 3; $i++) {

    // traverses down the FS tree 3 times before stopping
    $file = './' . str_repeat('../', $i) . 'app.php';

    if (is_file($file)) {
        $found = true;
        require_once $file;
        break;
    }
}

if (!$found) {
    fwrite(STDERR, 'Could not find app.php. Ensure you\'re running the server from your project folder.' . PHP_EOL);
}