<?php

// ensure the page is not cached
header("Cache-Control: no-cache, must-revalidate"); // HTTP 1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

// Get the parameters from the URL

// uncomment to make the parameter mandatory
// if (!isset($_GET['last_id'])) {
//   // Parameter is missing, exit the script with a message
//   exit("Error: 'last_id' parameter is missing.");
// }

$last_id = isset($_GET['last_id']) ? $_GET['last_id'] : 'R00000';

// Get the server name (domain)
$domain_name = $_SERVER['HTTP_HOST'];
$mas_path = '/home/mas/web/' . $domain_name . '/public_html/';
// echo '$mas_path is: ' . $mas_path . '<br>';

// Nina's contact id in this environment
$nina = 7608;

//required include files
require($mas_path . 'wp-blog-header.php');
require_once($mas_path . 'wp-config.php');
require_once($mas_path . 'wp-includes/wp-db.php');
// if you want to load all of wordpress, replace the three lines above with
// require_once($mas_path . 'wp-load.php');

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

class CiviCaseImport
{
  public function __construct() {}

  public function run()
  {
    // This is source and the target database
    // since MAS does not have the authority to create databases
    global $wpdb;
    global $nina;
    global $last_id;
    echo 'Source and Target Database is ' . $wpdb->dbname . ' and Ninas ID is ' . $nina . ' and $last_id is ' . $last_id . '<br>';

    // Fetch all rows from the service requests table
    $sr_sql = $wpdb->prepare("SELECT * FROM bgf_dataload_tServiceRequest WHERE RequestID > %s AND ClientID_Clean IS NOT NULL", $last_id);
    $sr_results = $wpdb->get_results($sr_sql);

    // Check if any rows are returned
    if (!empty($sr_results)) {
      // Iterate through each row
      $start_time = microtime(true);
      $timeout_limit = 28; // Set this slightly less than 30 seconds
      $check_interval = 100; // Check time every 100 rows
      $row_count = 0;
      foreach ($sr_results as $sr) {

        // Get client, client rep, and MAS rep
        $client_sql = $wpdb->prepare("SELECT id FROM civicrm_contact WHERE external_identifier = %s", $sr->ClientID);
        $client_id = $wpdb->get_var($client_sql);
        $ext_client_rep_id = strval(intval($sr->ClientRepID) + 1000000);
        $client_rep_sql = $wpdb->prepare("SELECT id FROM civicrm_contact WHERE external_identifier = %s", $ext_client_rep_id);
        $client_rep_id = $wpdb->get_var($client_rep_sql);
        $ext_mas_rep_id = strval(intval($sr->RepID) + 2000000);
        $mas_rep_sql = $wpdb->prepare("SELECT id FROM civicrm_contact WHERE external_identifier = %s", $ext_mas_rep_id);
        $mas_rep_id = $wpdb->get_var($mas_rep_sql);

        // UPDATE `bgf_dataload_tServiceRequest` 
        $update_results = $wpdb->update(
          'bgf_dataload_tServiceRequest',  // Table name
          array(
            'ClientID_Clean' => $client_id,
            'ClientRepID_Clean' => $client_rep_id,
            'MASRepID_Clean' => $mas_rep_id
          ),
          array('RequestID' => $sr->RequestID)  // WHERE clause
        );

        // echo '<br> Updating Request ID: ' . $sr->RequestID . ' Results: ' . $update_results . '<br>';
        $row_count++;
        if ($row_count % $check_interval == 0) {
          if ((microtime(true) - $start_time) >= $timeout_limit) {
            echo "Script is nearing the timeout limit after processing $row_count rows. <br>";
            $last_id = $sr->RequestID;

            // Construct the URL correctly using site_url or home_url
            $url = site_url('scripts/sr_clean.php?last_id=' . urlencode($last_id));

            // Output the correct URL
            echo 'Run <a href="' . esc_url($url) . '">' . esc_url($url) . '</a><br>';
            echo "Exiting gracefully.";
            exit;
          }
        }
      }
    } else {
      echo 'No data found.';
    }

    echo 'Works OK so far...<br>';
    // exit;
  }
}

function civiCaseImportFn()
{
  $civiCaseImport = new CiviCaseImport();
  $civiCaseImport->run();
}

civiCaseImportFn();

echo 'Now at end01...<br>';
