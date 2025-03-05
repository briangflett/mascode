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

        // Fetch a limited set of rows from the masrep_fix table
        $sr_sql = $wpdb->prepare(
            "SELECT * FROM bgf_civicrm_activity_23 WHERE id > %s AND Processed IS NOT TRUE ORDER BY id LIMIT %d",
            $last_id,
            500 // For example, to limit the results to x rows
        );
        $a23_results = $wpdb->get_results($sr_sql);

        // Iterate through each row
        foreach ($a23_results as $a23) {

            // Get the relationships for the old case id  (each array entry row is an array)
            // $relationships = \Civi\Api4\Relationship::get(TRUE)
            //     ->addSelect('id', 'contact_id_a', 'contact_id_b', 'relationship_type_id', 'case_id')
            //     ->addWhere('case_id', '=', $a23->old_case)
            //     ->setLimit(25)
            //     ->execute();

            // Update the relationships from the old to the new case id
            $results = \Civi\Api4\Relationship::update(TRUE)
                ->addValue('case_id', $a23->new_case)
                ->addWhere('case_id', '=', $a23->old_case)
                ->execute();

            $last_id  = $a23->id;
        }

        echo 'Works OK so far...<br>';
        // Construct the URL correctly using site_url or home_url
        $url = site_url('/wp-content/uploads/civicrm/ext/mascode/extern/case_masrep_fix.php?last_id=' . urlencode($last_id));
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
