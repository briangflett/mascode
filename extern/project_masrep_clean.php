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

        // Fetch all rows from the project table
        $p_sql = $wpdb->prepare("SELECT * FROM bgf_dataload_tProjectMASReps WHERE ProjectID > %s", $last_id);
        $p_results = $wpdb->get_results($p_sql);

        // Check if any rows are returned
        if (!empty($p_results)) {
            // Iterate through each row
            $start_time = microtime(true);
            $timeout_limit = 25; // Set this slightly less than 30 seconds
            $check_interval = 200; // Check time every 100 rows
            $row_count = 0;

            foreach ($p_results as $project) {

                // Get client, client rep, and MAS rep
                // $clientrep_sql = $wpdb->prepare("SELECT id FROM civicrm_contact WHERE external_identifier = %s", $project->ClientRepID);
                // $clientrep_id = $wpdb->get_var($clientrep_sql);
                // $ext_client_rep_id = strval(intval($project->ClientRepID) + 1000000);
                // $client_rep_sql = $wpdb->prepare("SELECT id FROM civicrm_contact WHERE external_identifier = %s", $ext_client_rep_id);
                // $client_rep_id = $wpdb->get_var($client_rep_sql);
                $ext_mas_rep_id = strval(intval($project->RepID) + 2000000);
                $mas_rep_sql = $wpdb->prepare("SELECT id FROM civicrm_contact WHERE external_identifier = %s", $ext_mas_rep_id);
                $mas_rep_id = $wpdb->get_var($mas_rep_sql);

                // Get the service request
                // if (!empty($project->RequestID)) {
                //     $sr_sql = $wpdb->prepare(
                //         "SELECT id FROM civicrm_case WHERE subject = %s",
                //         $project->RequestID
                //     );
                //     $sr_id = $wpdb->get_var($sr_sql);
                // } else {
                //     $sr_id = null;
                // }

                // // Get the project
                // if (!empty($project->ProjectID)) {
                //     $p_sql = $wpdb->prepare("SELECT id FROM civicrm_case WHERE subject = %s", $project->ProjectID);
                //     $p_id = $wpdb->get_var($p_sql);
                // } else {
                //     $p_id = null;
                // }

                // UPDATE the project
                $update_results = $wpdb->update(
                    'bgf_dataload_tProjectMASReps',  // Table name
                    array(
                        // 'ClientRepID_Clean' => $clientrep_id
                        // 'ClientRepID_Clean' => $client_rep_id,
                        'MASRepID_Clean' => $mas_rep_id
                        // 'RequestID_Clean' => $sr_id
                        // 'ProjectID_Clean' => $p_id
                    ),
                    array(
                        'ProjectID' => $project->ProjectID,
                        'RepID' => $project->RepID
                    )  // WHERE clause
                );

                // echo '<br> Updating Request ID: ' . $project->RequestID . ' Results: ' . $update_results . '<br>';

                $row_count++;
                if ($row_count % $check_interval == 0) {
                    if ((microtime(true) - $start_time) >= $timeout_limit) {
                        echo "Script is nearing the timeout limit after processing $row_count rows. <br>";
                        $last_id = $project->ProjectID;

                        // Construct the URL correctly using site_url or home_url
                        $url = site_url('scripts/project_masrep_clean.php?last_id=' . urlencode($last_id));

                        // Output the correct URL
                        echo 'Run <a href="' . esc_url($url) . '">' . esc_url($url) . '</a><br>';
                        echo "Exiting gracefully.";
                        exit;
                    } else {
                        echo '(microtime(true) - $start_time) is: ' . (microtime(true) - $start_time) . '  $timeout_limit is: ' . $timeout_limit . '  ProjectID is: ' . $project->ProjectID . '<br>';
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
