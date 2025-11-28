<?php

/**
 * Form for moving cases between organizations
 */
class CRM_Mascode_Form_MasCaseMove extends CRM_Core_Form {

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Move Cases Between Organizations'));
    
    // Add organization selection fields
    $this->addEntityRef('from_organization_id', ts('Move Cases FROM Organization'), [
      'entity' => 'Contact',
      'api' => [
        'params' => ['contact_type' => 'Organization'],
        'extra' => ['contact_type'],
      ],
      'placeholder' => ts('- Select Organization -'),
    ], true);
    
    $this->addEntityRef('to_organization_id', ts('Move Cases TO Organization'), [
      'entity' => 'Contact',
      'api' => [
        'params' => ['contact_type' => 'Organization'],
        'extra' => ['contact_type'],
      ],
      'placeholder' => ts('- Select Organization -'),
    ], true);
    
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Move Cases'),
        'isDefault' => true,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
  }
  
  /**
   * Add local and global form rules.
   */
  public function addRules() {
    $this->addFormRule(['CRM_Mascode_Form_MasCaseMove', 'formRule']);
  }
  
  /**
   * Global validation rules for the form.
   */
  public static function formRule($values) {
    $errors = [];
    
    if (!empty($values['from_organization_id']) && !empty($values['to_organization_id'])) {
      if ($values['from_organization_id'] == $values['to_organization_id']) {
        $errors['to_organization_id'] = ts('The source and target organizations must be different.');
      }
    }
    
    return $errors;
  }
  
  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    $values = $this->exportValues();

    $fromOrgId = $values['from_organization_id'];
    $toOrgId = $values['to_organization_id'];

    try {
      // Use the service to handle the business logic
      $service = new \Civi\Mascode\Service\CaseMoveService();
      $result = $service->moveCases($fromOrgId, $toOrgId);

      // Show success message
      if ($result['cases_moved'] > 0) {
        CRM_Core_Session::setStatus(
          ts('Successfully moved %1 case(s). %2', [
            1 => $result['cases_moved'],
            2 => $result['message']
          ]),
          ts('Cases Moved'),
          'success'
        );
      } else {
        CRM_Core_Session::setStatus($result['message'], ts('No Cases Moved'), 'info');
      }

      if (!empty($result['errors'])) {
        foreach ($result['errors'] as $error) {
          CRM_Core_Session::setStatus($error, ts('Warning'), 'warning');
        }
      }

    } catch (\Exception $e) {
      \Civi::log()->error('MasCaseMove.php - Form error in MasCaseMove: ' . $e->getMessage());
      CRM_Core_Session::setStatus(
        ts('Error occurred while moving cases: %1', [1 => $e->getMessage()]),
        ts('Error'),
        'error'
      );
    }
  }

}