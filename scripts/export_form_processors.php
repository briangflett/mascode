<?php
// scripts/export_form_processors.php
// Run with: cv scr scripts/export_form_processors.php

$output = [];

// Export Form Processors
try {
  $formProcessors = \Civi\Api4\FormProcessor::get()
    ->addWhere('name', 'LIKE', 'mas_%') // Adjust filter as needed
    ->execute();
  
  foreach ($formProcessors as $processor) {
    // Get form processor inputs
    $inputs = \Civi\Api4\FormProcessorInput::get()
      ->addWhere('form_processor_id', '=', $processor['id'])
      ->execute();
    
    // Get form processor outputs
    $outputs = \Civi\Api4\FormProcessorOutput::get()
      ->addWhere('form_processor_id', '=', $processor['id'])
      ->execute();
    
    $output[] = [
      'entity' => 'FormProcessor',
      'params' => [
        'version' => 4,
        'name' => $processor['name'],
        'title' => $processor['title'],
        'description' => $processor['description'],
        'is_active' => $processor['is_active'],
        'inputs' => $inputs->getArrayCopy(),
        'outputs' => $outputs->getArrayCopy(),
      ],
    ];
  }
  
  // Write to managed file
  $managedFile = \CRM_Mascode_ExtensionUtil::path('managed/FormProcessors.mgd.php');
  $managedContent = "<?php\n// Auto-generated Form Processor managed entities\n\n";
  $managedContent .= "return " . var_export($output, true) . ";\n";
  
  file_put_contents($managedFile, $managedContent);
  echo "Exported Form Processors to: $managedFile\n";
  
} catch (Exception $e) {
  echo "Error exporting Form Processors: " . $e->getMessage() . "\n";
  echo "This might be because FormProcessor API4 entities aren't available.\n";
  echo "Consider using the FormProcessor export/import functionality directly.\n";
}
