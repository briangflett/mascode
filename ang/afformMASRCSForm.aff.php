<?php

// Afform metadata for afformMASRCSForm
return array(
  'type' => 'form',
  'title' => 'Request for Consulting Services',
  'description' => 'MAS Request for Consulting Assistance form.',
  'server_route' => 'civicrm/mas-rcs-form',
  'is_public' => true,
  'permission' =>
  array(
    0 => '*always allow*',
  ),
  'redirect' => '/thank-you/',
  'submit_enabled' => true,
  'create_submission' => true,
  'email_confirmation_template_id' => 71,
  'icon' => 'fa-list-alt',
);
