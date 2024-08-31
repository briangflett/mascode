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

$last_id = isset($_GET['last_id']) ? $_GET['last_id'] : 0;

// Get the server name (domain)
$domain_name = $_SERVER['HTTP_HOST'];
if ($domain_name == 'www.masadvise.org') {
  $domain_name = 'masadvise.org';
}
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

    // Fetch all rows from the activity table
    $a_sql = $wpdb->prepare("SELECT * FROM bgf_dataload_tActivity WHERE ActivityID > %s AND RequestID <> '' AND ClientID_Clean IS NOT NULL", $last_id);
    $a_results = $wpdb->get_results($a_sql);

    // Check if any rows are returned
    if (!empty($a_results)) {
      // Iterate through each row
      $start_time = microtime(true);
      $timeout_limit = 25; // Set this slightly less than 30 seconds
      $check_interval = 200; // Check time every 100 rows
      $row_count = 0;

      foreach ($a_results as $activity) {
        // Source is Nina or the MAS Rep
        if (!empty($activity->MASRepID_Clean)) {
          $in_source = $activity->MASRepID_Clean;
        } else {
          $in_source = $nina;
        }

        // Create activity record
        $caseActivity = \Civi\Api4\Activity::create(TRUE)
          ->addValue('activity_type_id:label', 'Case Info Update')
          ->addValue('case_id', $activity->RequestID_Clean)
          ->addValue('source_contact_id', $in_source)
          ->addValue('target_contact_id', $activity->ClientRepID_Clean)
          ->addValue('details', $activity->Notes)
          ->addValue('activity_date_time', $activity->ContactDate)
          ->addValue('status_id:label', 'Completed')
          ->execute();

        $row_count++;
        if ($row_count % $check_interval == 0) {
          if ((microtime(true) - $start_time) >= $timeout_limit) {
            echo "Script is nearing the timeout limit after processing $row_count rows. <br>";
            $last_id = $activity->ActivityID;

            // Construct the URL correctly using site_url or home_url
            $url = site_url('scripts/activity_sr_import.php?last_id=' . urlencode($last_id));

            // Output the correct URL
            echo 'Run <a href="' . esc_url($url) . '">' . esc_url($url) . '</a><br>';
            echo "Exiting gracefully.";
            exit;
          } else {
            echo '(microtime(true) - $start_time) is: ' . (microtime(true) - $start_time) . '  $timeout_limit is: ' . $timeout_limit . '  ActivityID is: ' . $activity->ActivityID . '<br>';
          }
        }
      }
    } else {
      echo 'No data found.<br>';
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
