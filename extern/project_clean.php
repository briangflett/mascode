<?php

require_once 'dataload_header.php';

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

        echo 'CRM Database is ' . $wpdb->dbname . '  and Ninas ID is ' . $nina . '  and $last_id is ' . $last_id . '<br>';

        // Fetch all rows from the project table
        $p_sql = $wpdb->prepare("SELECT * FROM bgf_dataload_tProject WHERE ProjectID > %s AND Processed IS NOT TRUE", $last_id);
        $p_results = $wpdb->get_results($p_sql);

        // Check if any rows are returned
        if (!empty($p_results)) {
            // Iterate through each row
            $start_time = microtime(true);
            $timeout_limit = 25; // Set this slightly less than 30 seconds
            $check_interval = 200; // Check time every 100 rows
            $row_count = 0;

            foreach ($p_results as $project) {

                // Get the client
                $clients = \Civi\Api4\Contact::get(TRUE)
                    ->addSelect('id')
                    ->addWhere('external_identifier', '=', $project->ClientID)
                    ->execute();
                // Check if any client was found
                if ($clients->rowCount > 0) {
                    $client_id = $clients[0]['id']; // Correctly accessing the 'id' field
                } else {
                    // Handle the case where no contact was found with the given external_identifier
                    echo 'No client found for external identifier ' . $project->ClientID . '<br>';
                    $client_id = null; // Or handle it as needed
                }

                // $ext_client_rep_id = strval(intval($project->ClientRepID) + 1000000);
                // Get the client rep
                // $ext_mas_rep_id = strval(intval($project->RepID) + 2000000);
                // Get the MAS rep

                // Get the service request
                if (!empty($project->RequestID)) {

                    $srs = \Civi\Api4\CiviCase::get(TRUE)
                        ->addSelect('id')
                        ->addWhere('subject', '=', $project->RequestID)
                        ->execute();
                    // Check if any client was found
                    if ($srs->rowCount > 0) {
                        $sr_id = $srs[0]['id']; // Correctly accessing the 'id' field
                    } else {
                        // Handle the case where no sr was found 
                        echo 'No sr found for subject ' . $project->RequestID . '<br>';
                        $sr_id = null; // Or handle it as needed
                    }
                    // UPDATE the project
                    $update_results = $wpdb->update(
                        'bgf_dataload_tProject',  // Table name
                        array(
                            'ClientID_Clean' => $client_id,
                            // 'ClientRepID_Clean' => $client_rep_id,
                            // 'MASRepID_Clean' => $mas_rep_id,
                            'RequestID_Clean' => $sr_id
                            // 'ProjectID_Clean' => $p_id
                        ),
                        array('ProjectID' => $project->ProjectID)  // WHERE clause
                    );
                } else {
                    // UPDATE the project
                    $update_results = $wpdb->update(
                        'bgf_dataload_tProject',  // Table name
                        array(
                            'ClientID_Clean' => $client_id,
                            // 'ClientRepID_Clean' => $client_rep_id,
                            // 'MASRepID_Clean' => $mas_rep_id,
                            // 'RequestID_Clean' => $sr_id
                            // 'ProjectID_Clean' => $p_id
                        ),
                        array('ProjectID' => $project->ProjectID)  // WHERE clause
                    );
                }

                // echo '<br> Updating Request ID: ' . $project->RequestID . ' Results: ' . $update_results . '<br>';

                $row_count++;
                if ($row_count % $check_interval == 0) {
                    if ((microtime(true) - $start_time) >= $timeout_limit) {
                        echo "Script is nearing the timeout limit after processing $row_count rows. <br>";
                        $last_id = $project->ProjectID;

                        // Construct the URL correctly using site_url or home_url
                        $url = site_url('/wp-content/uploads/civicrm/ext/mascode/extern/project_clean.php?last_id=' . urlencode($last_id));

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
