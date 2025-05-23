<?php
// scripts/export_civirules.php
// Run with: cv scr scripts/export_civirules.php

$output = [];

// Export CiviRules
$rules = \Civi\Api4\CivirulesRule::get()
  ->addWhere('is_active', '=', TRUE)
  ->addWhere('name', 'LIKE', 'mas_%') // Adjust filter as needed
  ->execute();

foreach ($rules as $rule) {
  // Get rule actions
  $ruleActions = \Civi\Api4\CivirulesRuleAction::get()
    ->addWhere('rule_id', '=', $rule['id'])
    ->execute();
  
  // Get rule conditions
  $ruleConditions = \Civi\Api4\CivirulesRuleCondition::get()
    ->addWhere('rule_id', '=', $rule['id'])
    ->execute();
  
  $output['rules'][] = [
    'rule' => $rule,
    'actions' => $ruleActions->getArrayCopy(),
    'conditions' => $ruleConditions->getArrayCopy(),
  ];
}

// Write to managed file
$managedFile = \CRM_Mascode_ExtensionUtil::path('managed/CiviRules.mgd.php');
$managedContent = "<?php\n// Auto-generated CiviRules managed entities\n\n";
$managedContent .= "return " . var_export($output, true) . ";\n";

file_put_contents($managedFile, $managedContent);
echo "Exported CiviRules to: $managedFile\n";
