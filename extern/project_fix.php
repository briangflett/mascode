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
            1 // For example, to limit the results to x rows
        );
        $p_results = $wpdb->get_results($p_sql);

        // Check if any rows are returned
        if (!empty($p_results)) {
            // Iterate through each row
            foreach ($p_results as $project) {

                if ($project->PracticeArea == 'FAC') {
                    if (
                        $project->ProjectType == 'FAC' or
                        $project->ProjectType == ''
                    ) {
                        $project->PracticeArea = 'GEN';
                        $project->ProjectType = 'FAC';
                    } else {
                        $project->PracticeArea = $project->ProjectType;
                        $project->ProjectType = 'FAC';
                    }
                }

                if ($project->PracticeArea == 'PRESENT') {
                    if (
                        $project->ProjectType == 'FAC'
                        or
                        $project->ProjectType == ''
                    ) {
                        $project->PracticeArea = 'GEN';
                        $project->ProjectType = 'PRESENT';
                    } else {
                        $project->PracticeArea = $project->ProjectType;
                        $project->ProjectType = 'PRESENT';
                    }
                }

                if ($project->PracticeArea == '') {
                    if (
                        $project->ProjectType == 'FAC'
                        or
                        $project->ProjectType == ''
                    ) {
                        $project->PracticeArea = 'GEN';
                    } else {
                        $project->PracticeArea = $project->ProjectType;
                    }
                }

                if (
                    $project->ProjectType <> 'FAC' and
                    $project->ProjectType <> 'PRESENT'
                ) $project->ProjectType = '';

                $civiCaSE = \Civi\Api4\CiviCase::update(TRUE)
                    ->addValue('Projects.Practice_Area', $project->PracticeArea)
                    ->addValue('Projects.Project_Type', $project->ProjectType)
                    ->addValue('Projects.Notes', $project->Notes)
                    ->addWhere(
                        'subject',
                        '=',
                        $project->ProjectID
                    )
                    ->execute();
                $last_id = $project->ProjectID;
            }
        } else {
            echo 'No data found.';
        }

        echo 'Works OK so far...<br>';
        // Construct the URL correctly using site_url or home_url
        $url = site_url('/wp-content/uploads/civicrm/ext/mascode/extern/project_fix.php?last_id=' . urlencode($last_id));
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
