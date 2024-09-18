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

        // Fetch and update activities
        $activities = \Civi\Api4\Activity::get(TRUE)
            ->addSelect('id', 'details')
            ->addWhere('activity_type_id:label', '=', 'Case Info Update')
            ->addWhere('subject', 'IS EMPTY')
            ->setLimit(1000)
            ->execute();

        foreach ($activities as $activity) {
            // Update activity record
            if (strlen($activity["details"]) < 100) {
                $activity_subject = $activity["details"];
            } else {
                $activity_subject = substr($activity["details"], 0, 97) . '...';
            }

            $results = \Civi\Api4\Activity::update(TRUE)
                ->addValue('subject', $activity_subject)
                ->addWhere('id', '=', $activity["id"])
                ->execute();
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
