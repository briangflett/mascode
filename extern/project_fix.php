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

                $subject = str_pad($project->ProjectID, 5, '0', STR_PAD_LEFT);

                // update the practice area and type attributes of the project object
                [$practiceArea, $projectType] = $this->updatePracticeAreaAndType($project->PreacticeArea, $project->ProjectType);

                // Fetch the project hours
                $hr_sql = $wpdb->prepare(
                    "SELECT * FROM bgf_dataload_tProjectMASRepHours WHERE ProjectID = %d",
                    $project->ProjectID
                );
                $hr_results = $wpdb->get_results($hr_sql);
                if (!empty($hr_results)) {
                    $hours = $hr_results[0]->Hours;
                } else {
                    $hours = null;
                }

                $civiCaSE = \Civi\Api4\CiviCase::update(TRUE)
                    ->addValue('Projects.Practice_Area', $subject)
                    ->addValue('Projects.Practice_Area', $practiceArea)
                    ->addValue('Projects.Project_Type', $projectType)
                    ->addValue('Projects.Hours', $hours)
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
    private function updatePracticeAreaAndType($practiceArea, $projectType)
    {
        if ($practiceArea == 'FAC') {
            if (
                $projectType == 'FAC' or
                $projectType == ''
            ) {
                $practiceArea = 'GEN';
                $projectType = 'FAC';
            } else {
                $practiceArea = $projectType;
                $projectType = 'FAC';
            }
        }

        if ($practiceArea == 'PRESENT') {
            if (
                $projectType == 'FAC'
                or
                $projectType == ''
            ) {
                $practiceArea = 'GEN';
                $projectType = 'PRESENT';
            } else {
                $practiceArea = $projectType;
                $projectType = 'PRESENT';
            }
        }

        if ($practiceArea == '') {
            if (
                $projectType == 'FAC'
                or
                $projectType == ''
            ) {
                $practiceArea = 'GEN';
            } else {
                $practiceArea = $projectType;
            }
        }

        if (
            $projectType <> 'FAC' and
            $projectType <> 'PRESENT'
        ) $projectType = '';

        return [$practiceArea, $projectType];
    }
}

function civiCaseImportFn()
{
    $civiCaseImport = new CiviCaseImport();
    $civiCaseImport->run();
}

civiCaseImportFn();

echo 'Now at end01...<br>';
