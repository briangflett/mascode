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

        // Fetch a limited set of rows from the donations table
        $d_sql = $wpdb->prepare(
            "SELECT * FROM bgf_dataload_tDonation WHERE ID > %d AND Processed IS NOT TRUE ORDER BY ID LIMIT %d",
            $last_id,
            1000 // to limit the results to x rows
        );
        $d_results = $wpdb->get_results($d_sql);

        // Check if any rows are returned
        if (!empty($d_results)) {
            // Iterate through each row
            foreach ($d_results as $donation) {

                // Get the new Client ID
                $client_id = $this->getContactID($donation->ClientID, 'ClientID');

                // Get the new Client Rep ID
                $client_rep_id = $this->getContactID($donation->DonorRepID, 'ClientRepID');

                // Get the new MAS Rep ID
                $mas_rep_id = $this->getContactID($donation->RepID, 'MASRepID');

                // Add the donation 
                if ($client_id <> NULL) {
                    $this->createContribution($client_id, $mas_rep_id, $nina, $donation);
                } else {
                    if ($client_rep_id <> NULL) {
                        $this->createContribution($client_rep_id, $mas_rep_id, $nina, $donation);
                    } else {
                        echo 'Unable to add donation ' . $donation->ID . ' because the client ' . $client_id . ' and the client rep ' . $client_rep_id . ' are empty.  <br>';
                    }
                }

                $last_id = $donation->ID;
            }
        } else {
            echo 'No data found.';
        }

        echo 'Works OK so far...<br>';
        // Construct the URL correctly using site_url or home_url
        // $url = site_url('/wp-content/uploads/civicrm/ext/mascode/extern/project_import_2.php?last_id=' . urlencode($last_id) . '&XDEBUG_SESSION_START=bgf');
        $url = site_url('/wp-content/uploads/civicrm/ext/mascode/extern/donation_import.php?last_id=' . urlencode($last_id));
        // Output the correct URL
        echo 'If not complete, run <a href="' . esc_url($url) . '">' . esc_url($url) . '</a><br>';
        // exit;
    }

    private function getContactID($external_ID, $type)
    {
        if (!empty($external_ID)) {
            // adjust the external D number based on the type
            if ($type = 'ClientRepID') {
                $external_ID = $external_ID + 1000000;
            } else {
                if ($type = 'MASRepID') {
                    $external_ID = $external_ID + 2000000;
                }
            }
            // get the contact
            $civiContact = \Civi\Api4\Contact::get(TRUE)
                ->addSelect('id')
                ->addWhere('external_identifier', '=', $external_ID)
                ->execute();
            // Check if any contact was found
            if ($civiContact->count() > 0) {
                return $civiContact[0]['id'];
            } else {
                echo 'External ID ' . $external_ID . ' is missing.  <br>';
                return NULL;
            }
        }
    }

    private function createContribution($client_id, $mas_rep_id, $nina, $donation)
    {
        // step 1 - create the contribution
        if (!empty($mas_rep_id)) {
            $civiContribution = \Civi\Api4\Contribution::create(TRUE)
                ->addValue('contact_id', $client_id)
                ->addValue('financial_type_id:label', 'Donation')
                ->addValue('total_amount', $donation->Amount)
                ->addValue('receive_date', $donation->Date)
                ->addValue('Contributions.MAS_Rep', $mas_rep_id)
                ->addValue('Contributions.Practice_Area', $donation->PracticeArea)
                ->addValue('Contributions.External_Legacy_ID', $donation->ID)
                ->execute();
        } else {
            $civiContribution = \Civi\Api4\Contribution::create(TRUE)
                ->addValue('contact_id', $client_id)
                ->addValue('financial_type_id:label', 'Donation')
                ->addValue('total_amount', $donation->Amount)
                ->addValue('receive_date', $donation->Date)
                // ->addValue('Contributions.MAS_Rep', $mas_rep_id)
                ->addValue('Contributions.Practice_Area', $donation->PracticeArea)
                ->addValue('Contributions.External_Legacy_ID', $donation->ID)
                ->execute();
        }
        // step 2 - add the note to the contribution
        if (!empty($donation->Notes)) {
            $civiNote = \Civi\Api4\Note::create(TRUE)
                ->addValue('entity_table', 'civicrm_contribution')
                ->addValue('entity_id', $civiContribution[0]['id'])
                ->addValue('note', $donation->Notes)
                ->addValue('contact_id', $nina)
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
