<?php

require __DIR__ . '/vendor/autoload.php';

// Load every file under src or known PSR-4 folders
foreach (glob(__DIR__ . '/Hooks/*.php') as $file) {
    require_once $file;
}
foreach (glob(__DIR__ . '/Utils/*.php') as $file) {
    require_once $file;
}

// Now list all defined classes in your namespace
$classes = array_filter(get_declared_classes(), fn($c) => str_starts_with($c, 'Civi\\Mascode\\'));
print_r($classes);
