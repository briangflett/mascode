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

        // Fetch case statuses and build the associative array
        $caseStatuses = \Civi\Api4\OptionValue::get(TRUE)
            ->addSelect('label', 'grouping')
            ->addWhere('option_group_id:label', '=', 'Case Status')
            ->setLimit(50)
            ->execute();
        $statusMap = [];
        foreach ($caseStatuses as $status) {
            $statusMap[$status['label']] = $status['grouping'];
        }

        // Fetch a limited set of rows from the project table
        $p_sql = $wpdb->prepare(
            "SELECT * FROM bgf_dataload_tProject WHERE ProjectID > %d AND Processed IS NOT TRUE ORDER BY ProjectID LIMIT %d",
            $last_id,
            1000 // For example, to limit the results to x rows
        );
        $p_results = $wpdb->get_results($p_sql);

        // Check if any rows are returned
        if (!empty($p_results)) {
            // Iterate through each row
            foreach ($p_results as $project) {
                if (!empty($project->ClientID_Clean)) {
                    // Create a case
                    $civiCase = \Civi\Api4\CiviCase::create(TRUE)
                        ->addValue('case_type_id.name', 'project')
                        ->addValue('subject', $project->ProjectID)  // should this be the ProjectID or the Title?
                        ->addValue('creator_id', $nina)
                        ->addValue('start_date', $project->StartDate)
                        ->addValue('end_date', $project->EndDate)
                        ->addValue('status_id:label', $project->Status)
                        ->addValue('Projects_.Practice_Area', $project->PracticeArea)
                        ->addValue('Projects_.Project_Type', $project->ProjectType)
                        ->addValue('Projects_.Notes', $project->Notes)
                        // Title, ProjectType, DefinitionDocDate, CompletionDocDate, EvaluationDocDate, RequestID
                        ->addValue(
                            'contact_id',
                            [
                                $project->ClientID_Clean,
                            ]
                        )
                        ->execute();
                    $case_id = $civiCase[0]['id'];

                    // Should the relationships be active or inactive?
                    $this_status = $this->getStatusClass($project->Status, $statusMap);
                    if ($this_status == "Closed") {
                        $relationshipActive = FALSE;
                    } else {
                        $relationshipActive = TRUE;
                    }

                    // Create an activity to link to a Service Request if applicable
                    if (!empty($project->RequestID_Clean)) {
                        $this->linkSR($case_id, $project);
                    }

                    // link client reps
                    $this->linkClientReps($case_id, $project, $relationshipActive);

                    // link mas reps
                    $this->linkMASReps($case_id, $project, $relationshipActive);

                    // link activities
                    $this->linkActivities($case_id, $project);
                } else {
                    echo 'Unable to add project ' . $project->ProjectID . ' because external id ' . $project->ClientID . ' is missing.  <br>';
                }
                $last_id = $project->ProjectID;
            }
        } else {
            echo 'No data found.';
        }

        echo 'Works OK so far...<br>';
        // Construct the URL correctly using site_url or home_url
        // $url = site_url('/wp-content/uploads/civicrm/ext/mascode/extern/project_import_2.php?last_id=' . urlencode($last_id) . '&XDEBUG_SESSION_START=bgf');
        $url = site_url('/wp-content/uploads/civicrm/ext/mascode/extern/project_import_2.php?last_id=' . urlencode($last_id));
        // Output the correct URL
        echo 'Run <a href="' . esc_url($url) . '">' . esc_url($url) . '</a><br>';
        // exit;
    }

    // Lookup the status class by label
    private function getStatusClass($label, $statusMap)
    {
        if (isset($statusMap[$label])) {
            return $statusMap[$label];
        } else {
            return null;
        }
    }

    private function linkSR($case_id, $project)
    {
        global $nina;
        // Create a Link Cases activity, and link it to one case
        $civiActivity = \Civi\Api4\Activity::create(TRUE)
            ->addValue('activity_type_id:label', 'Link Cases')
            ->addValue('source_contact_id', $nina)
            ->addValue('target_contact_id', [
                $project->ClientID_Clean,
            ])
            ->addValue('case_id', $case_id)
            ->addValue('status_id:label', 'Completed')
            ->addValue('subject', 'Create link between - Service Request (CaseID: ' . $project->RequestID_Clean . ') and Project (CaseID: ' . $case_id . ').')
            ->execute();
        $activity_id = $civiActivity[0]['id'];
        // Then link the activity to the other case
        $civiCaseActivity = \Civi\Api4\CaseActivity::create(TRUE)
            ->addValue('case_id', $case_id)
            ->addValue('activity_id', $activity_id)
            ->execute();
    }
    private function linkClientReps($case_id, $project, $relationshipActive)
    {
        global $wpdb;
        // Fetch all rows from the project client reps table
        $cr_sql = $wpdb->prepare("SELECT * FROM bgf_dataload_tProjectClientReps WHERE ProjectID = %s AND Processed IS NOT TRUE", $project->ProjectID);
        $cr_results = $wpdb->get_results($cr_sql);
        // Check if any rows are returned
        if (!empty($cr_results)) {
            // Iterate through each row
            foreach ($cr_results as $clientRep) {
                if (!empty($clientRep->ClientRepID_Clean)) {
                    // Create a client rep relationship
                    $civiRelationship = \Civi\Api4\Relationship::create(TRUE)
                        ->addValue('contact_id_a', $clientRep->ClientRepID_Clean)     // client rep
                        ->addValue('contact_id_b', $project->ClientID_Clean)     // client
                        ->addValue('relationship_type_id:label', 'Case Client Rep is')
                        ->addValue('is_active', $relationshipActive)  // depends on project
                        ->addValue('case_id', $case_id)
                        ->execute();
                } else {
                    echo 'Unable to add client rep relationshp for project ' . $project->ProjectID . ' because external id 1000000 + ' . $clientRep->ClientRepID . ' is missing.  <br>';
                }
            }
        }
    }
    private function linkMASReps($case_id, $project, $relationshipActive)
    {
        global $wpdb;
        // Fetch all rows from the project MAS reps table
        $mr_sql = $wpdb->prepare("SELECT * FROM bgf_dataload_tProjectMASReps WHERE ProjectID = %s AND Processed IS NOT TRUE", $project->ProjectID);
        $mr_results = $wpdb->get_results($mr_sql);
        // Check if any rows are returned
        if (!empty($mr_results)) {
            // Iterate through each row
            foreach ($mr_results as $masRep) {
                if (!empty($masRep->MASRepID_Clean)) {
                    // Create a client rep relationship
                    $civiRelationship = \Civi\Api4\Relationship::create(TRUE)
                        ->addValue('contact_id_a', $masRep->MASRepID_Clean)     // mas rep
                        ->addValue('contact_id_b', $project->ClientID_Clean)     // client
                        ->addValue('relationship_type_id:label', 'Case MAS Rep is')
                        ->addValue('is_active', $relationshipActive)  // depends on project
                        ->addValue('case_id', $case_id)
                        ->execute();
                } else {
                    echo 'Unable to add MAS rep relationshp for project ' . $project->ProjectID . ' because external id 2000000 + ' . $masRep->MASRepID . ' is missing.  <br>';
                }
            }
        }
    }
    private function linkActivities($case_id, $project)
    {
        global $wpdb;
        global $nina;
        // Fetch all rows from the activity table
        $activity_sql = $wpdb->prepare("SELECT * FROM bgf_dataload_tActivity WHERE ProjectID = %s AND Processed IS NOT TRUE", $project->ProjectID);
        $activity_results = $wpdb->get_results($activity_sql);
        // Check if any rows are returned
        if (!empty($activity_results)) {
            // Iterate through each row
            foreach ($activity_results as $activity) {
                // Source is Nina or the MAS Rep
                if (!empty($activity->MASRepID_Clean)) {
                    $in_source = $activity->MASRepID_Clean;
                } else {
                    $in_source = $nina;
                }
                // Create activity record
                $civiActivity = \Civi\Api4\Activity::create(TRUE)
                    ->addValue('activity_type_id:label', 'Case Info Update')
                    ->addValue('case_id', $case_id)
                    ->addValue('source_contact_id', $in_source)
                    // ->addValue('target_contact_id', [$activity->ClientRepID_Clean])
                    ->addValue('details', $activity->Notes)
                    ->addValue('activity_date_time', $activity->ContactDate)
                    ->addValue('status_id:label', 'Completed')
                    ->execute();
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
