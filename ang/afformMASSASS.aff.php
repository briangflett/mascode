<?php

return array(
  'type' => 'form',
  'title' => 'Short Self Assessment Survey',
  'description' => 'MAS Self Assessment Survey - Short Version (21 questions)',
  'server_route' => 'civicrm/mas-sass-form',
  'is_public' => true,
  'permission' =>
  array(
    0 => '*always allow*',
  ),
  'redirect' => '/thank-you/',
  'submit_enabled' => true,
  'create_submission' => true,
  'email_confirmation_template_id' => null,
  'icon' => 'fa-clipboard-check',
  'placement' =>
  array(
    0 => 'msg_token_single',
  ),
  'permission_operator' => 'AND',
);
