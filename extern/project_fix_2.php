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
        echo 'Source and Target Database is ' . $wpdb->dbname . ' and Ninas ID is ' . $nina . ' and $last_id is ' . $last_id . '<br>';

        // Fetch a limited set of rows from the project table
        $p_sql = $wpdb->prepare(
            "SELECT * FROM bgf_dataload_tProject WHERE ProjectID > %d AND Processed IS NOT TRUE ORDER BY ProjectID LIMIT %d",
            $last_id,
            500 // For example, to limit the results to x rows
        );
        $p_results = $wpdb->get_results($p_sql);

        // Check if any rows are returned
        if (!empty($p_results)) {
            // Iterate through each row
            foreach ($p_results as $project) {

                $projectID = str_pad($project->ProjectID, 5, '0', STR_PAD_LEFT);

                // Fetch the project hours
                $hr_sql = $wpdb->prepare(
                    "SELECT * FROM bgf_dataload_tProjectMASRepHours WHERE ProjectID = %d",
                    $project->ProjectID
                );
                $hr_results = $wpdb->get_results($hr_sql);

                $civiCaSE = \Civi\Api4\CiviCase::update(TRUE)
                    ->addValue('subject', $projectID);

                if (!empty($hr_results)) {
                    $civiCaSE->addValue('Projects.Hours', $hr_results[0]->Hours);
                }

                $civiCaSE->addWhere('subject', '=', $project->ProjectID)
                    ->execute();
                $last_id = $project->ProjectID;
            }
        } else {
            echo 'No data found.';
        }

        echo 'Works OK so far...<br>';
        // Construct the URL correctly using site_url or home_url
        $url = site_url('/wp-content/uploads/civicrm/ext/mascode/extern/project_fix_2.php?last_id=' . urlencode($last_id));
        // Output the correct URL
        echo 'Run <a href="' . esc_url($url) . '">' . esc_url($url) . '</a><br>';
    }
}

function civiCaseImportFn()
{
    $civiCaseImport = new CiviCaseImport();
    $civiCaseImport->run();
}

civiCaseImportFn();

echo 'Now at end01...<br>';
