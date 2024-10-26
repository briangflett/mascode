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
            10
        );
        $sr_results = $wpdb->get_results($sr_sql);

        // Check if any rows are returned  (each array entry row is an object)
        if (!empty($sr_results)) {
            // Iterate through each row
            foreach ($sr_results as $sr) {

                // Referral is the source of interest
                [$referral, $notes] = $this->updateReferral($sr->Referral, $sr->Notes);

                $end_date = $this->updateEndDate(
                    $sr->RequestID,
                    $sr->Status,
                    $sr->ResolutionDate
                );

                $civiCaseUpdate = \Civi\Api4\CiviCase::update(TRUE)
                    ->addValue('end_date', $end_date)
                    ->addValue('Cases_SR_Projects_.Referral', $referral)
                    ->addValue('Cases_SR_Projects_.Notes', $notes)
                    ->addWhere('id', '=', $id)
                    ->execute();

                $last_id = $sr->RequestID;
            }
        } else {
            echo 'No data found.';
        }

        echo 'Works OK so far...<br>';
        // Construct the URL correctly using site_url or home_url
        $url = site_url('/wp-content/uploads/civicrm/ext/mascode/extern/sr_fix.php?last_id=' . urlencode($last_id));
        // Output the correct URL
        echo 'Run <a href="' . esc_url($url) . '">' . esc_url($url) . '</a><br>';
    }
    private function updateReferral($referral, $notes)
    {
        if ($referral === 'website') {
            $referral = 'Website';
        } else {
            if ($referral === 'Google') {
                $referral = 'Online Search';
            } else {
                if ($referral === 'Email from MAS') {
                    $referral = 'MAS email';
                }
            }
        }
        $validReferrals = [
            'MAS Client',
            'Repeat Client',
            'Another Agency',
            'Online Search',
            'Website',
            'MAS Consultant',
            'Other',
            'Workshop',
            'Social Media',
            'MAS email',
            // These will be converted and then disabled...
            'Word of Mouth',
            'Thought of MAS again',
            null
        ];
        if (!in_array($referral, $validReferrals, true)) {
            // Referral is invalid, move it to notes
            $notes = $notes . ' Referral: ' . $referral;
            $referral = 'Other';
        }
        return [$referral, $notes];
    }
    private function updateEndDate($requestID, $status, $resolutionDate)
    {
        if ($resolutionDate <> '') {
            return $resolutionDate;
        } else {
            $openStatuses = ["Open", "Request RCS", "Sent for Assignment"];
            if (in_array($status, $openStatuses, true)) {
                return $resolutionDate;
            } else {
                // Fetch the closed date
                $srh_sql = $wpdb->prepare(
                    "SELECT Date FROM bgf_dataload_tServiceRequestStatusHistory WHERE RequestID = %s",
                    $requestID
                );
                $srh_results = $wpdb->get_results($srh_sql);
                return $srh_results[0]['Date'];
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
