<?php

// ensure the page is not cached
header("Cache-Control: no-cache, must-revalidate"); // HTTP 1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$last_id = isset($_GET['last_id']) ? $_GET['last_id'] : 0;

// Get the server name (domain)
$domain_name = $_SERVER['HTTP_HOST'];
switch ($domain_name) {
    case "www.masadvise.org":
        $mas_path = '/home/mas/web/masadvise.org/public_html/';
        break;
    case "mas.myramani.com":
        $mas_path = '/home/mas/web/mas.myramani.com/public_html/';
        break;
    case "masdemo.localhost":
        $mas_path = '/home/brian/buildkit/build/masdemo/web/';
        // Do something for domain C
        echo "Handling domain C";
        break;
    default:
        // Exit with an error for any other value
        echo "Error: Invalid domain name.";
        exit(1); // Exit with an error status code
}
echo '$mas_path is: ' . $mas_path . '<br>';

// Nina's contact id in this environment
$nina = 7608;

// require Wordpress
require_once $mas_path . 'wp-load.php';

// Ensure this script is executed within WordPress
if (!defined('ABSPATH')) {
    exit("This script can only be run within WordPress.");
} else {
    echo 'ABSPATH is: ' . ABSPATH . '<br>';
}

// Check if the current user is logged in and has the Administrator role
if (current_user_can('administrator')) {
    echo "You are an Administrator.<br>";
} else {
    exit("You do not have sufficient permissions to access this script.");
}
