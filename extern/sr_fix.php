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
            300
        );
        $sr_results = $wpdb->get_results($sr_sql);

        // Check if any rows are returned  (each array entry row is an object)
        if (!empty($sr_results)) {
            // Iterate through each row
            foreach ($sr_results as $sr) {

                //Fetch the service request  (each array entry row is an array)
                $civiCaseGet = \Civi\Api4\CiviCase::get(TRUE)
                    ->addSelect('id', 'Cases_SR_Projects_.Referral', 'Cases_SR_Projects_.Notes')
                    ->addWhere('subject', '=', $sr->RequestID)
                    ->execute();

                $id = $civiCaseGet[0]['id'];

                // Referral is the source of interest
                [$referral, $notes] = $this->updateReferral($civiCaseGet[0]['Cases_SR_Projects_.Referral'], $civiCaseGet[0]['Cases_SR_Projects_.Notes']);

                $civiCaseUpdate = \Civi\Api4\CiviCase::update(TRUE)
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
}

function civiCaseImportFn()
{
    $civiCaseImport = new CiviCaseImport();
    $civiCaseImport->run();
}

civiCaseImportFn();

echo 'Now at end01...<br>';
