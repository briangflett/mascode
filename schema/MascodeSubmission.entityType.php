<?php
use CRM_Mascode_ExtensionUtil as E;

return [
  'name' => 'Mascodesubmission',
  'table' => 'civicrm_mascode_submission',
  'class' => 'CRM_Mascode2_DAO_Mascodesubmission',
  'getInfo' => fn() => [
    'title' => E::ts('Mascodesubmission'),
    'title_plural' => E::ts('Mascodesubmissions'),
    'description' => E::ts('FIXME'),
    'log' => TRUE,
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique Mascodesubmission ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'contact_id' => [
      'title' => E::ts('Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to Contact'),
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
  ],
  'getIndices' => fn() => [],
  'getPaths' => fn() => [],
];
