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

                //Fetch the project
                $civiCaseGet = \Civi\Api4\CiviCase::get(TRUE)
                    ->addSelect('id', 'subject', 'Cases_SR_Projects_.Referral', 'Projects.Notes')
                    ->addWhere('subject', '=', $project->ProjectID)
                    ->execute();

                $id = $civiCaseGet[0]->id;
                $subject = $civiCaseGet[0]->subject;
                $subject = str_pad($subject, 5, '0', STR_PAD_LEFT);

                // Referral is the source of interest
                $referral = $civiCaseGet[0]->Cases_SR_Projects_ . Referral;
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
                if (in_array($referral, $validReferrals, true)) {
                    // Refferral is valid
                    $notes = $civiCaseGet[0]->Projects . Notes;
                } else {
                    // Referral is invalid, move it to notes
                    $notes = $civiCaseGet[0]->Projects . Notes . ' Referral: ' . $referral;
                    $referral = 'Other';
                }

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

                $civiCaseUpdate = \Civi\Api4\CiviCase::update(TRUE)
                    ->addValue('subject', $subject)
                    ->addValue('Cases_SR_Projects_.Referral', $referral)
                    ->addValue('Projects.Notes', $notes)
                    ->addValue('Projects.Hours', $hours)
                    ->addWhere('id', '=', $id)
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
