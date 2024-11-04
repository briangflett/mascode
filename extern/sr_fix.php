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

        // Fetch a limited set of rows from the service requests table
        $sr_sql = $wpdb->prepare(
            "SELECT * FROM bgf_dataload_tServiceRequest WHERE RequestID > %s AND Processed IS NOT TRUE ORDER BY RequestID LIMIT %d",
            $last_id,
            500
        );
        $sr_results = $wpdb->get_results($sr_sql);

        // Iterate through each row
        foreach ($sr_results as $sr) {

            $civiCase = \Civi\Api4\CiviCase::get(TRUE)
                ->addSelect('id')
                ->addWhere('subject', '=', $sr->RequestID)
                ->execute();

            $count = $civiCase->count();  // Store count in variable first
            // Check count() directly
            if ($count == 1) {

                $case_id = $civiCase[0]['id'];
                $case_end_date = $civiCase[0]['end_date'];

                $sr->ClientID_Clean = $this->getClientID($sr->ClientID);
                $end_date = $this->updateEndDate($sr->RequestID, $sr->Status, $sr->ResolutionDate, $sr->LastUpdateDate);

                if ($end_date <> $case_end_date) {
                    $results = \Civi\Api4\CiviCase::update(TRUE)
                        ->addValue('end_date', $end_date)
                        ->addWhere('id', '=', $case_id)
                        ->execute();
                }

                if (
                    $sr->RequestID > 'R20000'
                    and $sr->RequestID < 'R24999'
                    and !empty($end_date)
                )

                    $civiActivity = \Civi\Api4\Activity::create(TRUE)
                        ->addValue('activity_type_id:label', 'Change Case Status')
                        ->addValue('source_contact_id', $nina)
                        ->addValue('target_contact_id', [
                            $sr->ClientID_Clean,
                        ])
                        ->addValue('case_id', $case_id)
                        ->addValue('status_id:label', 'Completed')
                        ->addValue('subject', 'Case status changed to ' . $sr->Status)
                        ->addvalue('activity_date_time', $end_date)
                        ->execute();
            } else {
                echo 'Request with Subject = ' . $sr->RequestID . ' not found. <br>';
            }

            $last_id = $sr->RequestID;
        }

        echo 'Works OK so far...<br>';
        // Construct the URL correctly using site_url or home_url
        $url = site_url('/wp-content/uploads/civicrm/ext/mascode/extern/sr_fix.php?last_id=' . urlencode($last_id));
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
    private function updateEndDate($requestID, $status, $resolutionDate, $lastUpdateDate)
    {
        global $wpdb;

        // Early return if resolution date exists
        if (!empty($resolutionDate)) {
            return $resolutionDate;
        }

        // Define open statuses
        $openStatuses = ["Open", "Request RCS", "Sent for Assignment"];

        // Return empty resolution date for open statuses
        if (in_array($status, $openStatuses, true)) {
            return '';  // or return null, depending on your needs
        }

        // Fetch the closed date for non-open statuses
        $srh_sql = $wpdb->prepare(
            "SELECT Date FROM bgf_dataload_tServiceRequestStatusHistory 
         WHERE RequestID = %s 
         ORDER BY Date DESC 
         LIMIT 1",
            $requestID
        );

        $srh_results = $wpdb->get_results($srh_sql);

        // Check if results exist before accessing array
        if (!empty($srh_results)) {
            return $srh_results[0]->Date;
        } else {
            return $lastUpdateDate;
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
