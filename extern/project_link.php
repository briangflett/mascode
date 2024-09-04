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

        // Fetch all rows from the project table
        $p_sql = $wpdb->prepare("SELECT * FROM bgf_dataload_tProject WHERE ProjectID > %s AND RequestID_Clean IS NOT NULL AND Processed IS NOT TRUE", $last_id);
        $p_results = $wpdb->get_results($p_sql);

        // Check if any rows are returned
        if (!empty($p_results)) {
            // Iterate through each row
            $start_time = microtime(true);
            $timeout_limit = 25; // Set this slightly less than 30 seconds
            $check_interval = 200; // Check time every 100 rows
            $row_count = 0;

            foreach ($p_results as $project) {

                // Create a case
                $civiCase = \Civi\Api4\CiviCase::create(TRUE)
                    ->addValue('case_type_id.name', 'project')
                    ->addValue('subject', $project->ProjectID)  // should this be the ProjectID or the Title?
                    ->addValue('creator_id', $nina)
                    ->addValue('start_date', $project->StartDate)
                    ->addValue('end_date', $project->EndDate)
                    ->addValue('status_id:label', $project->Status)
                    ->addValue('Projects_.Practice_Area', $project->PracticeArea)
                    ->addValue('Projects_.Project_Type', $project->ProjectType)
                    ->addValue('Projects_.Notes', $project->Notes)
                    // Title, ProjectType, DefinitionDocDate, CompletionDocDate, EvaluationDocDate, RequestID
                    ->addValue(
                        'contact_id',
                        [
                            $project->ClientID_Clean
                        ]
                    )
                    ->execute();

                // Create an activity to link to a Service Request if applicable
                $civiActivty = \Civi\Api4\Activity::create(TRUE)
                    ->addValue('activity_type_id:label', 'Link Cases')
                    ->addValue('source_contact_id', $nina)
                    ->addValue('target_contact_id', [
                        $project->ClientID_Clean,
                    ])
                    ->addValue('case_id', 4358)
                    ->addValue('status_id:label', 'Completed')
                    ->addValue('subject', 'Test linking two cases')
                    ->execute();


                $row_count++;
                if ($row_count % $check_interval == 0) {
                    if ((microtime(true) - $start_time) >= $timeout_limit) {
                        echo "Script is nearing the timeout limit after processing $row_count rows. <br>";
                        $last_id = $project->ProjectID;

                        // Construct the URL correctly using site_url or home_url
                        $url = site_url('/wp-content/uploads/civicrm/ext/mascode/extern/project_import.php?last_id=' . urlencode($last_id));

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
