<?php
// file:  Civi/Mascode/Patches/GenericHookEventPatch.php

namespace Civi\Mascode\Patches;

class GenericHookEventPatch {
  
  /**
   * Apply the patch to fix the GenericHookEvent::__get() method
   */
  public static function apply() {
    $originalFile = \Civi::paths()->getPath('[civicrm.root]/Civi/Core/Event/GenericHookEvent.php');
    
    if (!file_exists($originalFile)) {
      \Civi::log()->error('Cannot apply GenericHookEvent patch: file not found at ' . $originalFile);
      return false;
    }
    
    $fileContent = file_get_contents($originalFile);
    
    // Search for the problematic method
    $pattern = '/public function &__get\(\$name\) \{.*?if \(isset\(\$this->hookFieldsFlip\[\$name\]\)\) \{.*?return \$this->hookValues\[\$this->hookFieldsFlip\[\$name\]\];.*?\}.*?\}/s';
    
    // Replacement method with the fix
    $replacement = 'public function &__get($name) {
    if (isset($this->hookFieldsFlip[$name])) {
      return $this->hookValues[$this->hookFieldsFlip[$name]];
    }
    $null = null;
    return $null;
  }';
    
    $updatedContent = preg_replace($pattern, $replacement, $fileContent);
    
    // Check if the replacement was successful
    if ($updatedContent === $fileContent) {
      \Civi::log()->warning('GenericHookEvent patch not applied: method signature not found or already patched');
      return false;
    }
    
    // Apply the patch
    if (file_put_contents($originalFile, $updatedContent)) {
      \Civi::log()->info('GenericHookEvent patch successfully applied');
      return true;
    } else {
      \Civi::log()->error('Failed to write patched GenericHookEvent file');
      return false;
    }
  }
}