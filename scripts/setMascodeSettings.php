<?php

/**
 * Set MAS Code settings
 * 
 * This script sets up the admin contact for ServiceRequestToProject action
 * by finding a contact with the MAS_Rep subtype and specific email.
 */

echo "Setting up MAS Code settings...\n";

try {
  // Set admin contact for ServiceRequestToProject action
  $adminContact = \Civi\Api4\Contact::get(FALSE)
    ->addSelect('id', 'display_name')
    ->addWhere('contact_sub_type', '=', 'MAS_Rep')
    ->addWhere('email_primary.email', '=', 'info@masadvise.org')
    ->execute()
    ->first();
    
  $adminId = $adminContact['id'] ?? NULL;
  
  if ($adminId) {
    $oldSetting = \Civi::settings()->get('mascode_admin_contact_id');
    \Civi::settings()->set('mascode_admin_contact_id', $adminId);
    
    echo "Admin contact set to: {$adminContact['display_name']} (ID: $adminId)\n";
    
    if ($oldSetting !== NULL && $oldSetting != $adminId) {
      echo "Previous setting was ID: $oldSetting\n";
    }
  } else {
    echo "Warning: Could not find contact with MAS_Rep subtype and email info@masadvise.org\n";
    exit(1);
  }
  
  // You could add other settings here
  echo "Settings update completed successfully.\n";
  exit(0);
  
} catch (\Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
  exit(1);
}