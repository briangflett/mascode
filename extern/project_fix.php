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
                if (
                    $project->ProjectID > 20000
                    and $project->ProjectID < 24999
                    and $project->EndDate <> ""
                ) {
                    $projectID = str_pad($project->ProjectID, 5, '0', STR_PAD_LEFT);
                    $subject = $projectID . ' ' . $project->Title;
                    $project->ClientID_Clean = $this->getClientID($project->ClientID);
                    $end_date = $this->updateEndDate($projectID, $project->Status, $project->EndDate);

                    $civiCase = \Civi\Api4\CiviCase::get(TRUE)
                        ->addSelect('id')
                        ->addWhere('subject', '=', $subject)
                        ->execute();

                    $count = $civiCase->count();  // Store count in variable first
                    // Check count() directly
                    if ($count == 1) {

                        $case_id = $civiCase[0]['id'];

                        $civiActivity = \Civi\Api4\Activity::create(TRUE)
                            ->addValue('activity_type_id:label', 'Change Case Status')
                            ->addValue('source_contact_id', $nina)
                            ->addValue('target_contact_id', [
                                $project->ClientID_Clean,
                            ])
                            ->addValue('case_id', $case_id)
                            ->addValue('status_id:label', 'Completed')
                            ->addValue('subject', 'Case status changed from Active to ' . $project->Status)
                            ->addvalue('activity_date_time', $end_date)
                            ->execute();
                    } else {
                        echo "Project with Subject = $subject not found. <br>";
                    }

                    $last_id = $project->ProjectID;
                }
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
    // Get the CiviCRM Client ID given the External (Access) Client ID
    private function getClientID($clientID)
    {
        $contacts = \Civi\Api4\Contact::get(TRUE)
            ->addSelect('id')
            ->addWhere('external_identifier', '=', $clientID)
            ->execute();
        $count = $contacts->count();  // Store count in variable first
        // Check count() directly
        if ($count == 0) {
            echo "External Client ID $clientID not found. <br>";
            return null;
        } elseif ($count == 1) {
            return $contacts[0]['id']; // Accessing 'id' as an array       
        } else {
            echo "Multiple External Client ID $clientID found. <br>";
            return null;
        }
    }
    private function updateEndDate($projectID, $status, $endDate)
    {
        if ($endDate <> '') {
            return $endDate;
        } else {
            $openStatuses = ["Open", "Active", "On Hold"];
            if (in_array($status, $openStatuses, true)) {
                return $endDate;
            } else {
                // Fetch the closed date
                $ph_sql = $wpdb->prepare(
                    "SELECT Date FROM bgf_dataload_tProjectStatusHistory WHERE ProjectID = %s",
                    $projectID
                );
                $ph_results = $wpdb->get_results($ph_sql);
                return $ph_results[0]['Date'];
            }
        }
    }
}
function civiCaseImportFn()
{
    $civiCaseImport = new CiviCaseImport();
    $civiCaseImport->run();
}

civiCaseImportFn();

echo 'Now at end01...<br>';
