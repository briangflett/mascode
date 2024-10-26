<?php

// Relationship type is 9 for case coordinator, 18 for MAS Rep

require_once 'dataload_header.php';

class CiviCaseImport
{
    public function __construct() {}

    public function run()
    {
        global $last_id;

        // Get the relationships sorted by case_id  (each array entry row is an array)
        $relationships = \Civi\Api4\Relationship::get(TRUE)
            ->addSelect('id', 'contact_id_a', 'contact_id_b', 'relationship_type_id', 'case_id')
            ->addWhere('case_id', 'IS NOT NULL')
            ->addWhere('case_id', '>', $last_id)
            ->addWhere('relationship_type_id', 'IN', [9, 18])
            ->addOrderBy('case_id', 'ASC')
            ->setLimit(25)
            ->execute();

        $case_id = null;
        $coord_rel_id = null;
        $masRep_id = null;
        $masRep_rel_id = null;

        foreach ($relationships as $relationship) {
            if ($relationship['case_id'] <> $case_id) {
                if (
                    ($coord_rel_id <> null) &&
                    ($masRep_id <> null) &&
                    ($masRep_rel_id <> null)
                ) {
                    // Update the Coordinator and delete the MAS rep
                    $results = \Civi\Api4\Relationship::update(TRUE)
                        ->addValue('contact_id_a', $masRep_id)
                        ->addWhere('id', '=', $coord_rel_id)
                        ->execute();
                    $results = \Civi\Api4\Relationship::delete(TRUE)
                        ->addWhere('id', '=', $masRep_rel_id)
                        ->execute();
                }
                $last_id = $case_id;
                $case_id = $relationship['case_id'];
            }
            if ($relationship['relationship_type_id'] = 9) {
                $coord_rel_id = $relationship['id'];
            } else {
                if ($relationship['relationship_type_id'] = 18) {
                    $masRep_id = $relationship['contact_id_a'];
                    $masRep_rel_id = $relationship['id'];
                }
            }
        }

        echo 'Works OK so far...<br>';
        // Construct the URL correctly using site_url or home_url
        $url = site_url('/wp-content/uploads/civicrm/ext/mascode/extern/coordinator_fix.php?last_id=' . urlencode($last_id));
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
