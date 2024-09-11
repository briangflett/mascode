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
  }
}

function civiCaseImportFn()
{
  $civiCaseImport = new CiviCaseImport();
  $civiCaseImport->run();
}

civiCaseImportFn();

echo 'Now at end01...<br>';
