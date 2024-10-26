<?php

// This is the example file provided by Edsel from JMA...

//required include files
require('wp-blog-header.php');
require_once("wp-config.php");
require_once("wp-includes/wp-db.php");

class CiviCase_Import
{
  /**
   * Constructor.
   *
   * @since 0.1
   */
  public function __construct() {}

  public function run()
  {
    // This is the target database
    global $wpdb;

    // This is the source database from where you will fetch cases to be imported. 
    // You can create this database by importing the CSV file of cases into mySQL.
    $servername = "localhost";
    $dbname = "civicase_import";
    $username = "";
    $password = "";

    // connect to database using credientials
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
    }

    // Fetch all of the rows in the target tablle within civicase_import db
    $sql = "SELECT * FROM case_data";
    $result = $conn->query($sql);
    $cases = [];
    if ($result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
        $cases[] = $row;
      }
    } else {
      echo "0 results";
    }

    if (!empty($cases)) {
      // Loop through each case.
      foreach ($cases as $case) {
        // Create cases, adding fields as necessary
        $civiCase = \Civi\Api4\CiviCase::create(FALSE)
          ->addValue('case_type_id.name', 'service_request')
          ->addValue('subject', $case['RequestID'])
          ->addValue('status_id', '')
          ->execute();

        // Create case contact
        $caseContact = \Civi\Api4\CaseContact::create(FALSE)
          ->addValue('case_id', $civiCase['id'])
          ->addValue('contact_id', $case['ClientID'])
          ->execute();

        // Create activity record
        // This might probably need to be broken ito it's own loop, as there can be multiple activities for one case
        $activity = \Civi\Api4\Activity::create(FALSE)
          ->addValue('activity_type_id', '')
          ->addValue('source_contact_id', '')
          ->addValue('target_contact_id', '')
          ->execute();

        // Link activity created above to case
        // Might need to be broken into it's own loop.
        $caseActivity = \Civi\Api4\CaseActivity::create(FALSE)
          ->addValue('case_id', $civiCase['id'])
          ->addValue('activity_id', $activity['id'])
          ->execute();

        // Create case roles
        // You can create multiple case roles by duplicating this below.
        $caseRole = \Civi\Api4\Relationship::create(FALSE)
          ->addValue('case_id', $civiCase['id'])
          ->addValue('contact_id_a', '')
          ->addValue('contact_id_b', '')
          ->addValue('relationship_type_id', '')
          ->execute();
      }
    }

    $conn->close();
    exit;
  }
}

function importCases()
{
  $caseimport = new CiviCase_Import();
  $caseimport->run();
}

importCases();
