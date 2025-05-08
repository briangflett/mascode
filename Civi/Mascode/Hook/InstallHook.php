<?php
// file: Civi/Mascode/Hook/InstallHook.php

/**
 * Install CiviRules triggers, actions, & conditions.
 * Install mas_parameters.
 */

namespace Civi\Mascode\Hook;

class InstallHook
{
  public static function handle(): void
  {
    self::createOptionGroupAndValues();
    // self::registerCiviRuleAction();
    \CRM_Civirules_Utils_Upgrader::insertTriggersFromJson('../CiviRules/triggers.json');
    \CRM_Civirules_Utils_Upgrader::insertActionsFromJson('../CiviRules/actions.json');
    \CRM_Civirules_Utils_Upgrader::insertConditionsFromJson('../CiviRules/conditions.json');
  }
  private static function createOptionGroupAndValues(): void
  {
    $og = \Civi\Api4\OptionGroup::get()
      ->addSelect('id')
      ->addWhere('name', '=', 'mas_parameters')
      ->execute()
      ->first();

    if (!$og) {
      $ogId = \Civi\Api4\OptionGroup::create()
        ->addValue('name', 'mas_parameters')
        ->addValue('title', 'MAS Parameters')
        ->addValue('data_type', 'String')
        ->addValue('is_reserved', 1)
        ->execute()
        ->first()['id'];
    } else {
      $ogId = $og['id'];
    }

    // Helper function to create values
    $valuesToEnsure = [
      'project_code_last' => '01001',
      'sr_code_last' => 'R01001',
    ];

    foreach ($valuesToEnsure as $name => $value) {
      $existing = \Civi\Api4\OptionValue::get()
        ->addWhere('option_group_id', '=', $ogId)
        ->addWhere('name', '=', $name)
        ->execute()
        ->first();

      if (!$existing) {
        \Civi\Api4\OptionValue::create()
          ->addValue('option_group_id', $ogId)
          ->addValue('name', $name)
          ->addValue('label', ucwords(str_replace('_', ' ', $name)))
          ->addValue('value', $value)
          ->execute();
      }
    }
  }

  // private static function registerCiviRuleAction(): void
  // {
  //   $exists = $civiRulesActions = \Civi\Api4\CiviRulesAction::get(TRUE)
  //     ->addSelect('id')
  //     ->addWhere('name', '=', 'mas_create_project_from_sr')
  //     ->setLimit(25)
  //     ->execute();

  //   if (!$exists) {
  //     \Civi\Api4\CiviRulesAction::create(TRUE)
  //       ->addValue('name', 'mas_create_project_from_sr')
  //       ->addValue('label', 'Create a Project from a Service Request')
  //       ->addValue('class_name', 'CRM_Mascode_Action_ServiceRequestToProject')
  //       ->addValue('is_active', TRUE)
  //       ->execute();
  //   }
  // }
}
