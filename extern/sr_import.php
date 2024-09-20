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

    // Fetch a limited set of rows from the service requests table
    $sr_sql = $wpdb->prepare(
      "SELECT * FROM bgf_dataload_tServiceRequest WHERE RequestID > %s AND Processed IS NOT TRUE ORDER BY RequestID LIMIT %d",
      $last_id,
      200 // For example, to limit the results to x rows
    );
    $sr_results = $wpdb->get_results($sr_sql);

    // Check if any rows are returned
    if (!empty($sr_results)) {
      // Iterate through each row
      foreach ($sr_results as $sr) {
        // Get the CiviCRM ClientID
        $sr->ClientID_Clean = $this->getClientID($sr->ClientID);

        if (!empty($sr->ClientID_Clean)) {
          // insert or update BGF ??
          // if ($sr->RequestID > 'R24168') {
          // Create a case
          $civiCase = \Civi\Api4\CiviCase::create(TRUE)
            ->addValue('case_type_id.name', 'service_request')
            ->addValue('subject', $sr->RequestID)
            ->addValue('creator_id', $nina)
            ->addValue('start_date', $sr->InitiationDate)
            ->addValue('end_date', $sr->ResolutionDate)
            ->addValue('status_id:label', $sr->Status)
            ->addValue('Cases_SR_Projects_.Practice_Area', $sr->PracticeArea)
            ->addValue('Cases_SR_Projects_.Referral', $sr->Referral)
            ->addValue('Cases_SR_Projects_.Notes', $sr->Notes)
            ->addValue(
              'contact_id',
              [
                $sr->ClientID_Clean
              ]
            )
            ->execute();
          // } else {
          //   // Update a case
          //   $civiCase = \Civi\Api4\CiviCase::update(TRUE)
          //     ->addValue('case_type_id.name', 'service_request')
          //     ->addValue('creator_id', $nina)
          //     ->addValue('start_date', $sr->InitiationDate)
          //     ->addValue('end_date', $sr->ResolutionDate)
          //     ->addValue('status_id:label', $sr->Status)
          //     ->addValue('Cases_SR_Projects_.Practice_Area', $sr->PracticeArea)
          //     ->addValue('Cases_SR_Projects_.Referral', $sr->Referral)
          //     ->addValue('Cases_SR_Projects_.Notes', $sr->Notes)
          //     ->addValue(
          //       'contact_id',
          //       [
          //         $sr->ClientID_Clean
          //       ]
          //     )
          //     ->addWhere('subject', '=', $sr->RequestID)
          //     ->execute();
          // }

          // Access the ID of the first created or updated case
          $case_id = $civiCase[0]['id'];

          // Should the relationships be active or inactive?
          $this_status = $this->getStatusClass($sr->Status, $statusMap);
          if ($this_status == "Closed") {
            $relationshipActive = FALSE;
          } else {
            $relationshipActive = TRUE;
          }

          // Create the MAS Rep
          if (!empty($sr->RepID)) {
            // Get the MAS rep
            $ext_mas_rep_id = strval(intval($sr->RepID) + 2000000);
            $mas_rep_id = $this->getClientID($ext_mas_rep_id);
            if (!empty($mas_rep_id)) {
              try {
                $caseRole = \Civi\Api4\Relationship::create(TRUE)
                  ->addValue('contact_id_a', $mas_rep_id)
                  ->addValue('contact_id_b', $sr->ClientID_Clean)
                  ->addValue('relationship_type_id:label', 'Case MAS Rep is')
                  ->addValue('case_id', $case_id)
                  ->addValue('is_active', $relationshipActive)
                  ->execute();
              } catch (Exception $e) {
                // Handle duplicate error or log the message
                echo "Error creating MAS relationship: " . $e->getMessage() . " for Case:$case_id Client:$sr->ClientID_Clean MAS Rep:$mas_rep_id<br>";
              }
            } else {
              echo "Unable to add MAS rep for service request $sr->RequestID because external id $mas_rep_id is missing.  <br>";
            }
          }

          // Create the Client Rep
          if (!empty($sr->ClientRepID)) {
            // Get the client rep
            $ext_client_rep_id = strval(intval($sr->ClientRepID) + 1000000);
            $client_rep_id = $this->getClientID($ext_client_rep_id);
            if (!empty($client_rep_id)) {
              try {
                $caseRole = \Civi\Api4\Relationship::create(TRUE)
                  ->addValue('contact_id_a', $client_rep_id)
                  ->addValue('contact_id_b', $sr->ClientID_Clean)
                  ->addValue('relationship_type_id:label', 'Case Client Rep is')
                  ->addValue('case_id', $case_id)
                  ->addValue('is_active', $relationshipActive)
                  ->execute();
              } catch (Exception $e) {
                // Handle duplicate error or log the message
                echo "Error creating Client relationship: " . $e->getMessage() . " for Case:$case_id Client:$sr->ClientID_Clean Client Rep:$client_rep_id<br>";
              }
            } else {
              echo "Unable to add client rep for service request $sr->RequestID because external id $ext_client_rep_id is missing.  <br>";
            }
          }

          // link activities
          $this->linkActivities($case_id, $sr);
        } else {
          echo "Unable to add service request $sr->RequestID because Client external id $sr->ClientID is missing.  <br>";
        }
        $last_id  = $sr->RequestID;
      }
    } else {
      echo 'No data found.<br>';
    }

    echo 'Works OK so far...<br>';
    // Construct the URL correctly using site_url or home_url
    $url = site_url('/wp-content/uploads/civicrm/ext/mascode/extern/sr_import.php?last_id=' . urlencode($last_id));
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

    if (!empty($contacts)) {
      return $contacts[0]['id']; // Accessing 'id' as an array
    } else {
      echo "External Client ID $clientID not found. <br>";
      return null;
    }
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
  private function linkActivities($case_id, $sr)
  {
    global $wpdb;
    global $nina;
    // Fetch all rows from the activity table
    $activity_sql = $wpdb->prepare("SELECT * FROM bgf_dataload_tActivity WHERE RequestID = %s AND Processed IS NOT TRUE", $sr->RequestID);
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
        if (strlen($activity->Notes) < 100) {
          $activity_subject = $activity->Notes;
          $activity->Notes = '';
        } else {
          $activity_subject = substr($activity->Notes, 0, 97) . '...';
        }
        $civiActivity = \Civi\Api4\Activity::create(TRUE)
          ->addValue('activity_type_id:label', 'Case Info Update')
          ->addValue('case_id', $case_id)
          ->addValue('source_contact_id', $in_source)
          ->addValue('subject', $activity_subject)
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
