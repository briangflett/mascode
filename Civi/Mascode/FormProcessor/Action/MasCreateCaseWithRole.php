<?php
// File: mascode/CRM/Mascode/FormProcessor/Action/CreateCaseWithRole.php

namespace CRM\Mascode\FormProcessor\Action;

use CRM_Formprocessor_FormprocessorAction;
use CRM_Core_Transaction;

/**
 * Class for creating a case and assigning a role in one transaction
 */
class MasCreateCaseWithRole extends CRM_Formprocessor_FormprocessorAction {
  
  /**
   * Returns the specification of the configuration options for the actual action.
   *
   * @return array
   */
  public function getSpecification() {
    return [
      'case_type_id' => [
        'name' => 'case_type_id',
        'title' => E::ts('Case Type'),
        'description' => E::ts('The case type to create'),
        'type' => 'Integer',
        'required' => TRUE,
        'options' => $this->getCaseTypeOptions(),
      ],
      'client_id' => [
        'name' => 'client_id',
        'title' => E::ts('Client Contact ID'),
        'description' => E::ts('The contact ID of the client for whom the case is created'),
        'type' => 'Integer',
        'required' => TRUE,
      ],
      'subject' => [
        'name' => 'subject',
        'title' => E::ts('Case Subject'),
        'description' => E::ts('The subject of the case'),
        'type' => 'String',
        'required' => TRUE,
      ],
      'status_id' => [
        'name' => 'status_id',
        'title' => E::ts('Case Status'),
        'description' => E::ts('The initial status of the case'),
        'type' => 'Integer',
        'options' => $this->getCaseStatusOptions(),
        'required' => TRUE,
      ],
      'start_date' => [
        'name' => 'start_date',
        'title' => E::ts('Case Start Date'),
        'description' => E::ts('The start date of the case (defaults to today)'),
        'type' => 'Date',
        'required' => FALSE,
      ],
      'role_contact_id' => [
        'name' => 'role_contact_id',
        'title' => E::ts('Role Contact ID'),
        'description' => E::ts('The contact ID of the person to assign to the case role'),
        'type' => 'Integer',
        'required' => TRUE,
      ],
      'relationship_type_id' => [
        'name' => 'relationship_type_id',
        'title' => E::ts('Relationship Type'),
        'description' => E::ts('The relationship type to create'),
        'type' => 'Integer',
        'options' => $this->getRelationshipTypeOptions(),
        'required' => TRUE,
      ],
      'creator_contact_id' => [
        'name' => 'creator_contact_id',
        'title' => E::ts('Creator Contact ID'),
        'description' => E::ts('Contact ID of the person creating the case (optional)'),
        'type' => 'Integer',
        'required' => FALSE,
      ],
    ];
  }

  /**
   * Returns the specification of the output parameters of this action.
   *
   * @return array
   */
  public function getOutputSpecification() {
    return [
      'case_id' => [
        'name' => 'case_id',
        'type' => 'Integer',
      ],
      'client_id' => [
        'name' => 'client_id',
        'type' => 'Integer',
      ],
      'role_contact_id' => [
        'name' => 'role_contact_id',
        'type' => 'Integer',
      ],
      'relationship_id' => [
        'name' => 'relationship_id',
        'type' => 'Integer',
      ],
    ];
  }

  /**
   * Run the action
   *
   * @param array $parameters
   *   The parameters to execute the action with.
   * @param array $output
   *   The output values.
   * @return void
   */
  protected function doAction(array $parameters, array &$output) {
    // Start a transaction
    $transaction = new CRM_Core_Transaction();
    
    try {
      // Set default start date if not provided
      if (empty($parameters['start_date'])) {
        $parameters['start_date'] = date('Y-m-d');
      }
      
      // Set creator ID if not provided
      $creatorId = $parameters['creator_contact_id'] ?? \CRM_Core_Session::getLoggedInContactID();
      
      // Create the case using API4
      $case = \Civi\Api4\Case::create()
        ->setValues([
          'case_type_id' => $parameters['case_type_id'],
          'contact_id' => $parameters['client_id'],
          'subject' => $parameters['subject'],
          'status_id' => $parameters['status_id'],
          'start_date' => $parameters['start_date'],
          'created_id' => $creatorId,
        ])
        ->execute()
        ->first();
      
      // Create the relationship/role using API4
      $relationship = \Civi\Api4\Relationship::create()
        ->setValues([
          'case_id' => $case['id'],
          'contact_id_a' => $parameters['role_contact_id'],
          'contact_id_b' => $parameters['client_id'],
          'relationship_type_id' => $parameters['relationship_type_id'],
          'is_active' => 1,
          'start_date' => $parameters['start_date'],
        ])
        ->execute()
        ->first();
      
      // Set output parameters
      $output['case_id'] = $case['id'];
      $output['client_id'] = $parameters['client_id'];
      $output['role_contact_id'] = $parameters['role_contact_id'];
      $output['relationship_id'] = $relationship['id'];
      
      // Commit the transaction
      $transaction->commit();
    }
    catch (\Exception $e) {
      // Roll back transaction on error
      $transaction->rollback();
      throw new \Exception('Error creating case with role: ' . $e->getMessage());
    }
  }
  
  /**
   * Helper function to get case type options
   */
  private function getCaseTypeOptions() {
    $options = [];
    
    try {
      $caseTypes = \Civi\Api4\CaseType::get()
        ->addSelect('id', 'name', 'title')
        ->addOrderBy('title')
        ->execute();
      
      foreach ($caseTypes as $caseType) {
        $options[$caseType['id']] = $caseType['title'];
      }
    }
    catch (\Exception $e) {
      \Civi::log()->error('Failed to retrieve case types: ' . $e->getMessage());
    }
    
    return $options;
  }
  
  /**
   * Helper function to get case status options
   */
  private function getCaseStatusOptions() {
    $options = [];
    
    try {
      $statuses = \Civi\Api4\OptionValue::get()
        ->addSelect('value', 'label')
        ->addWhere('option_group_id:name', '=', 'case_status')
        ->addOrderBy('weight')
        ->execute();
      
      foreach ($statuses as $status) {
        $options[$status['value']] = $status['label'];
      }
    }
    catch (\Exception $e) {
      \Civi::log()->error('Failed to retrieve case statuses: ' . $e->getMessage());
    }
    
    return $options;
  }
  
  /**
   * Helper function to get relationship type options relevant for cases
   */
  private function getRelationshipTypeOptions() {
    $options = [];
    
    try {
      $relationshipTypes = \Civi\Api4\RelationshipType::get()
        ->addSelect('id', 'name_a_b', 'label_a_b')
        ->addWhere('is_active', '=', TRUE)
        ->addOrderBy('label_a_b')
        ->execute();
      
      foreach ($relationshipTypes as $type) {
        $options[$type['id']] = $type['label_a_b'];
      }
    }
    catch (\Exception $e) {
      \Civi::log()->error('Failed to retrieve relationship types: ' . $e->getMessage());
    }
    
    return $options;
  }
}