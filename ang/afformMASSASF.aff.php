<?php

return array (
  'type' => 'form',
  'requires' => NULL,
  'entity_type' => NULL,
  'join_entity' => NULL,
  'title' => 'Full Self Assessment Survey',
  'description' => 'MAS Self Assessment Survey - Full Version (35 questions)',
  'placement' => 
  array (
    0 => 'msg_token_single',
  ),
  'placement_filters' => 
  array (
  ),
  'placement_weight' => NULL,
  'tags' => NULL,
  'icon' => 'fa-clipboard-list',
  'server_route' => 'civicrm/mas-sasf-form',
  'is_public' => true,
  'permission' => 
  array (
    0 => '*always allow*',
  ),
  'permission_operator' => 'AND',
  'redirect' => '/thank-you/',
  'submit_enabled' => true,
  'submit_limit' => NULL,
  'create_submission' => true,
  'manual_processing' => false,
  'allow_verification_by_email' => false,
  'email_confirmation_template_id' => NULL,
  'autosave_draft' => false,
  'navigation' => NULL,
  'modified_date' => '2025-07-08 12:36:19',
  'layout' => 
  array (
    0 => 
    array (
      '#tag' => 'af-form',
      'ctrl' => 'afform',
      '#children' => 
      array (
        0 => 
        array (
          '#text' => '
  ',
        ),
        1 => 
        array (
          '#tag' => 'af-entity',
          'data' => 
          array (
          ),
          'type' => 'Organization',
          'name' => 'Organization1',
          'label' => 'Organization 1',
          'actions' => 
          array (
            'create' => false,
            'update' => true,
          ),
          'security' => 'FBAC',
          'url-autofill' => '0',
          'autofill' => 'relationship:Employer of',
          'autofill-relationship' => 'Individual1',
          'contact-dedupe' => 'Organization.Unsupervised',
        ),
        2 => 
        array (
          '#text' => '
  ',
        ),
        3 => 
        array (
          '#tag' => 'af-entity',
          'data' => 
          array (
          ),
          'type' => 'Individual',
          'name' => 'Individual1',
          'label' => 'Individual 1',
          'actions' => 
          array (
            'create' => false,
            'update' => true,
          ),
          'security' => 'FBAC',
          'autofill' => 'entity_id',
          'contact-dedupe' => 'Individual.Supervised',
        ),
        4 => 
        array (
          '#text' => '
  ',
        ),
        5 => 
        array (
          '#tag' => 'af-entity',
          'data' => 
          array (
            'source_contact_id' => 'Individual1',
            'activity_type_id' => 74,
            'status_id' => 2,
            'assignee_contact_id' => 
            array (
              0 => 'Organization1',
            ),
            'subject' => 'SAS Full',
          ),
          'type' => 'Activity',
          'name' => 'Activity1',
          'label' => 'Activity 1',
          'actions' => 
          array (
            'create' => true,
            'update' => false,
          ),
          'security' => 'FBAC',
        ),
        6 => 
        array (
          '#text' => '
  ',
        ),
        7 => 
        array (
          '#tag' => 'div',
          'class' => 'af-container',
          '#children' => 
          array (
            0 => 
            array (
              '#text' => '
    ',
            ),
            1 => 
            array (
              '#tag' => 'fieldset',
              'af-fieldset' => 'Organization1',
              'class' => 'af-container af-container-style-pane',
              'af-title' => 'Organization Information',
              'style' => 'border: 3px solid #619ee6; background-color: #ffffff',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => '
      ',
                ),
                1 => 
                array (
                  '#tag' => 'div',
                  'class' => 'af-container af-layout-inline',
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#text' => '
        ',
                    ),
                    1 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'organization_name',
                      'defn' => 
                      array (
                        'required' => true,
                        'input_attrs' => 
                        array (
                        ),
                      ),
                    ),
                    2 => 
                    array (
                      '#text' => '
      ',
                    ),
                  ),
                ),
                2 => 
                array (
                  '#text' => '
    ',
                ),
              ),
            ),
            2 => 
            array (
              '#text' => '
    ',
            ),
            3 => 
            array (
              '#tag' => 'fieldset',
              'af-fieldset' => 'Individual1',
              'class' => 'af-container af-container-style-pane',
              'af-title' => 'Contact Information',
              'style' => 'border: 3px solid #619ee6; background-color: #ffffff',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => '
      ',
                ),
                1 => 
                array (
                  '#tag' => 'div',
                  'class' => 'af-container af-layout-inline',
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#text' => '
        ',
                    ),
                    1 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'first_name',
                      'defn' => 
                      array (
                        'required' => true,
                        'input_attrs' => 
                        array (
                        ),
                      ),
                    ),
                    2 => 
                    array (
                      '#text' => '
        ',
                    ),
                    3 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'last_name',
                      'defn' => 
                      array (
                        'required' => true,
                        'input_attrs' => 
                        array (
                        ),
                      ),
                    ),
                    4 => 
                    array (
                      '#text' => '
        ',
                    ),
                    5 => 
                    array (
                      '#tag' => 'div',
                      'af-join' => 'Email',
                      'actions' => 
                      array (
                        'update' => true,
                        'delete' => true,
                      ),
                      'data' => 
                      array (
                        'is_primary' => true,
                      ),
                      '#children' => 
                      array (
                        0 => 
                        array (
                          '#text' => '
          ',
                        ),
                        1 => 
                        array (
                          '#tag' => 'div',
                          'actions' => 
                          array (
                            'update' => true,
                            'delete' => true,
                          ),
                          'class' => 'af-container',
                          '#children' => 
                          array (
                            0 => 
                            array (
                              '#text' => '
            ',
                            ),
                            1 => 
                            array (
                              '#tag' => 'div',
                              'class' => 'af-container af-layout-inline',
                              '#children' => 
                              array (
                                0 => 
                                array (
                                  '#text' => '
              ',
                                ),
                                1 => 
                                array (
                                  '#tag' => 'af-field',
                                  'name' => 'email',
                                  'defn' => 
                                  array (
                                    'required' => true,
                                    'input_attrs' => 
                                    array (
                                    ),
                                  ),
                                ),
                                2 => 
                                array (
                                  '#text' => '
              ',
                                ),
                                3 => 
                                array (
                                  '#tag' => 'af-field',
                                  'name' => 'location_type_id',
                                  'defn' => 
                                  array (
                                    'afform_default' => '1',
                                    'input_attrs' => 
                                    array (
                                    ),
                                    'required' => false,
                                    'label' => false,
                                    'input_type' => 'Hidden',
                                  ),
                                ),
                                4 => 
                                array (
                                  '#text' => '
              ',
                                ),
                                5 => 
                                array (
                                  '#tag' => 'af-field',
                                  'name' => 'is_primary',
                                  'defn' => 
                                  array (
                                    'afform_default' => '1',
                                    'label' => false,
                                    'input_type' => 'Hidden',
                                  ),
                                ),
                                6 => 
                                array (
                                  '#text' => '
            ',
                                ),
                              ),
                            ),
                            2 => 
                            array (
                              '#text' => '
          ',
                            ),
                          ),
                        ),
                        2 => 
                        array (
                          '#text' => '
        ',
                        ),
                      ),
                    ),
                    6 => 
                    array (
                      '#text' => '
      ',
                    ),
                  ),
                ),
                2 => 
                array (
                  '#text' => '
    ',
                ),
              ),
            ),
            4 => 
            array (
              '#text' => '
    ',
            ),
            5 => 
            array (
              '#tag' => 'fieldset',
              'af-fieldset' => 'Activity1',
              'class' => 'af-container af-container-style-pane',
              'af-title' => 'Full Self Assessment Survey',
              'style' => 'border: 3px solid #619ee6',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => '
      ',
                ),
                1 => 
                array (
                  '#tag' => 'div',
                  'class' => 'af-markup',
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#text' => '
        
        
        ',
                    ),
                    1 => 
                    array (
                      '#tag' => 'p',
                      '#children' => 
                      array (
                        0 => 
                        array (
                          '#tag' => 'strong',
                          '#children' => 
                          array (
                            0 => 
                            array (
                              '#text' => 'Please respond "yes" or "no" to the statements below.',
                            ),
                          ),
                        ),
                      ),
                    ),
                    2 => 
                    array (
                      '#text' => '
        ',
                    ),
                    3 => 
                    array (
                      '#tag' => 'p',
                      '#children' => 
                      array (
                        0 => 
                        array (
                          '#tag' => 'strong',
                          '#children' => 
                          array (
                            0 => 
                            array (
                              '#text' => 'Before beginning please note:',
                            ),
                          ),
                        ),
                      ),
                    ),
                    4 => 
                    array (
                      '#text' => '
        ',
                    ),
                    5 => 
                    array (
                      '#tag' => 'p',
                      '#children' => 
                      array (
                        0 => 
                        array (
                          '#text' => 'When there are multiple elements to a statement, for example things like "policies including a, b, c" or "statements exist and are reviewed by the Board", answer no if any part of the statement is not applicable to your organization. Also, answer no if you are uncertain on any aspect of a statement.',
                        ),
                      ),
                    ),
                    6 => 
                    array (
                      '#text' => '
        ',
                    ),
                    7 => 
                    array (
                      '#tag' => 'p',
                      'style' => 'color: #c00000;',
                      '#children' => 
                      array (
                        0 => 
                        array (
                          '#tag' => 'strong',
                          '#children' => 
                          array (
                            0 => 
                            array (
                              '#text' => 'Very few not-for-profit/charitable organizations would ever be able to answer "yes" to all the questions below.',
                            ),
                          ),
                        ),
                        1 => 
                        array (
                          '#text' => ' The statements describe the ideal in each sphere and together form the picture of what NFPs should aspire to. In some instances, getting there may be quite simple; redo your Mission statement so it better reflects what you do, for example. In others, more work is needed – creating policies, grappling with program design, increasing diversity within the organization etc. The self-assessment tool will highlight for the Board and staff where you may wish to focus your attention. And of course, MAS is here to help too.',
                        ),
                      ),
                    ),
                    8 => 
                    array (
                      '#text' => '
      
      
      ',
                    ),
                  ),
                ),
                2 => 
                array (
                  '#text' => '
      ',
                ),
                3 => 
                array (
                  '#tag' => 'div',
                  'actions' => 
                  array (
                    'update' => true,
                    'delete' => true,
                  ),
                  'class' => 'af-container',
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#text' => '
        ',
                    ),
                    1 => 
                    array (
                      '#tag' => 'div',
                      'class' => 'af-markup',
                      '#children' => 
                      array (
                        0 => 
                        array (
                          '#text' => '
          
          
          ',
                        ),
                        1 => 
                        array (
                          '#tag' => 'h4',
                          'style' => 'color: #617de6; border-bottom: 2px solid #617de6; padding-bottom: 5px; margin-top: 20px;',
                          '#children' => 
                          array (
                            0 => 
                            array (
                              '#text' => 'Strategy',
                            ),
                          ),
                        ),
                        2 => 
                        array (
                          '#text' => '
        
        
        ',
                        ),
                      ),
                    ),
                    2 => 
                    array (
                      '#text' => '
        ',
                    ),
                    3 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q01_vision_mission_clear',
                      'defn' => 
                      array (
                        'label' => '1. There are clear and succinct Vision, Mission (Mandate) statements that accurately describe what your organization does and strives for.',
                      ),
                    ),
                    4 => 
                    array (
                      '#text' => '
        ',
                    ),
                    5 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q02_unique_services',
                      'defn' => 
                      array (
                        'label' => '2. There is no other organization, in your community, that offers the same services.',
                      ),
                    ),
                    6 => 
                    array (
                      '#text' => '
        ',
                    ),
                    7 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q03_strategic_goals',
                      'defn' => 
                      array (
                        'label' => '3. There is a set of up to 6 strategic goals (Strategic Plan) updated no more than four years ago',
                      ),
                    ),
                    8 => 
                    array (
                      '#text' => '
        ',
                    ),
                    9 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q04_annual_operational_plan',
                      'defn' => 
                      array (
                        'label' => '4. Staff develop an annual operational plan, based on/reflecting the strategic goals (Strategic Plan) of the organization. The plan includes clear measurable goals and tracks activities/outputs and if possible, impacts which are reported on at least semi-annually, to the Board.',
                      ),
                    ),
                    10 => 
                    array (
                      '#text' => '
        ',
                    ),
                    11 => 
                    array (
                      '#tag' => 'div',
                      'class' => 'af-markup',
                      '#children' => 
                      array (
                        0 => 
                        array (
                          '#text' => '
          
          
          ',
                        ),
                        1 => 
                        array (
                          '#tag' => 'h4',
                          'style' => 'color: #617de6; border-bottom: 2px solid #617de6; padding-bottom: 5px; margin-top: 20px;',
                          '#children' => 
                          array (
                            0 => 
                            array (
                              '#text' => 'Governance',
                            ),
                          ),
                        ),
                        2 => 
                        array (
                          '#text' => '
        
        
        ',
                        ),
                      ),
                    ),
                    12 => 
                    array (
                      '#text' => '
        ',
                    ),
                    13 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q05_governance_documents',
                      'defn' => 
                      array (
                        'label' => '5. Board governance documents and policies are up to date and reviewed on a regular basis. This includes by-laws, Articles (Letters Patent), Committee structures/roles/terms of reference, meeting Minutes (record of decisions), conflict of interest.',
                      ),
                    ),
                    14 => 
                    array (
                      '#text' => '
        ',
                    ),
                    15 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q06_board_composition',
                      'defn' => 
                      array (
                        'label' => '6. The Board of Directors has the appropriate size, composition (including diverse representation) and skill sets to support the organization.',
                      ),
                    ),
                    16 => 
                    array (
                      '#text' => '
        ',
                    ),
                    17 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q07_board_committees',
                      'defn' => 
                      array (
                        'label' => '7. The Board makes use of committees where they are helpful and does so effectively.',
                      ),
                    ),
                    18 => 
                    array (
                      '#text' => '
        ',
                    ),
                    19 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q08_board_effectiveness',
                      'defn' => 
                      array (
                        'label' => '8. The Board works effectively, makes timely decisions, and positively supports the organization.',
                      ),
                    ),
                    20 => 
                    array (
                      '#text' => '
        ',
                    ),
                    21 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q09_board_self_assessment',
                      'defn' => 
                      array (
                        'label' => '9. The Board periodically carries out a self-assessment (at least every 3 years)',
                      ),
                    ),
                    22 => 
                    array (
                      '#text' => '
        ',
                    ),
                    23 => 
                    array (
                      '#tag' => 'div',
                      'class' => 'af-markup',
                      '#children' => 
                      array (
                        0 => 
                        array (
                          '#text' => '
          
          
          ',
                        ),
                        1 => 
                        array (
                          '#tag' => 'h4',
                          'style' => 'color: #617de6; border-bottom: 2px solid #617de6; padding-bottom: 5px; margin-top: 20px;',
                          '#children' => 
                          array (
                            0 => 
                            array (
                              '#text' => 'Finance',
                            ),
                          ),
                        ),
                        2 => 
                        array (
                          '#text' => '
        
        
        ',
                        ),
                      ),
                    ),
                    24 => 
                    array (
                      '#text' => '
        ',
                    ),
                    25 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q10_budget_financial_statements',
                      'defn' => 
                      array (
                        'label' => '10. There is an annual budget (balanced) and at least quarterly financial statements based on it, and these are reviewed and accepted by the Board of Directors or Board Committee.',
                      ),
                    ),
                    26 => 
                    array (
                      '#text' => '
        ',
                    ),
                    27 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q11_risk_management',
                      'defn' => 
                      array (
                        'label' => '11. There is a risk management (financial, reputational, operational) analysis and plan in place updated every two to three years.',
                      ),
                    ),
                    28 => 
                    array (
                      '#text' => '
        ',
                    ),
                    29 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q12_contingency_fund',
                      'defn' => 
                      array (
                        'label' => '12. The organization has at least a three-month operating costs contingency fund set aside.',
                      ),
                    ),
                    30 => 
                    array (
                      '#text' => '
        ',
                    ),
                    31 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q13_audit_review',
                      'defn' => 
                      array (
                        'label' => '13. An annual audit or review engagement is carried out with Board oversight.',
                      ),
                    ),
                    32 => 
                    array (
                      '#text' => '
        ',
                    ),
                    33 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q14_funding_contracts',
                      'defn' => 
                      array (
                        'label' => '14. All funding contracts, donor receipting, and relational follow up with funders, are in good order and the Board is kept updated on these.',
                      ),
                    ),
                    34 => 
                    array (
                      '#text' => '
        ',
                    ),
                    35 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q15_donations_policy',
                      'defn' => 
                      array (
                        'label' => '15. The Board has established a donations policy and/or there is a donor management strategy in place.',
                      ),
                    ),
                    36 => 
                    array (
                      '#text' => '
        ',
                    ),
                    37 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q16_financial_reporting',
                      'defn' => 
                      array (
                        'label' => '16. Financial reporting to CRA (Canada Revenue Agency) and other funders is done on-time, accurately, and is reviewed and approved by the Board/Finance Committee.',
                      ),
                    ),
                    38 => 
                    array (
                      '#text' => '
        ',
                    ),
                    39 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q17_financial_viability',
                      'defn' => 
                      array (
                        'label' => '17. The Board, senior staff, and where applicable Members are comfortable with the financial viability of the organization.',
                      ),
                    ),
                    40 => 
                    array (
                      '#text' => '
        ',
                    ),
                    41 => 
                    array (
                      '#tag' => 'div',
                      'class' => 'af-markup',
                      '#children' => 
                      array (
                        0 => 
                        array (
                          '#text' => '
          
          
          ',
                        ),
                        1 => 
                        array (
                          '#tag' => 'h4',
                          'style' => 'color: #617de6; border-bottom: 2px solid #617de6; padding-bottom: 5px; margin-top: 20px;',
                          '#children' => 
                          array (
                            0 => 
                            array (
                              '#text' => 'Human Resources',
                            ),
                          ),
                        ),
                        2 => 
                        array (
                          '#text' => '
        
        
        ',
                        ),
                      ),
                    ),
                    42 => 
                    array (
                      '#text' => '
        ',
                    ),
                    43 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q18_executive_director_confidence',
                      'defn' => 
                      array (
                        'label' => '18. The Executive Director/most senior staff person has the full confidence of the Board of Directors, and they work well together.',
                      ),
                    ),
                    44 => 
                    array (
                      '#text' => '
        ',
                    ),
                    45 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q19_executive_limitations',
                      'defn' => 
                      array (
                        'label' => '19. The Board has set executive limitations and/or supports the operational autonomy of the executive staff.',
                      ),
                    ),
                    46 => 
                    array (
                      '#text' => '
        ',
                    ),
                    47 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q20_sufficient_qualified_staff',
                      'defn' => 
                      array (
                        'label' => '20. There are a sufficient number of appropriately qualified employees (and volunteers) to achieve the mission of the organization.',
                      ),
                    ),
                    48 => 
                    array (
                      '#text' => '
        ',
                    ),
                    49 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q21_diverse_employee_cohort',
                      'defn' => 
                      array (
                        'label' => '21. The employee cohort is diverse and representative of the wider community.',
                      ),
                    ),
                    50 => 
                    array (
                      '#text' => '
        ',
                    ),
                    51 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q22_job_descriptions_evaluations',
                      'defn' => 
                      array (
                        'label' => '22. All employees have a job description (regularly reviewed and updated), an annualized work plan, and an annual evaluation, kept on file.',
                      ),
                    ),
                    52 => 
                    array (
                      '#text' => '
        ',
                    ),
                    53 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q23_hr_policy_manual',
                      'defn' => 
                      array (
                        'label' => '23. There is a current Human Resources Policy/Manual outlining (at a minimum) hiring protocols, terms of employment that comply with any relevant legislation, as well as complaint, conflict of interest, DEI policy, and abuse/harassment policies.',
                      ),
                    ),
                    54 => 
                    array (
                      '#text' => '
        ',
                    ),
                    55 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q24_compensation_review',
                      'defn' => 
                      array (
                        'label' => '24. Employee compensation is reviewed on a regular basis and is in line with that of other similar organizations (including Executive Director).',
                      ),
                    ),
                    56 => 
                    array (
                      '#text' => '
        ',
                    ),
                    57 => 
                    array (
                      '#tag' => 'div',
                      'class' => 'af-markup',
                      '#children' => 
                      array (
                        0 => 
                        array (
                          '#text' => '
          
          
          ',
                        ),
                        1 => 
                        array (
                          '#tag' => 'h4',
                          'style' => 'color: #617de6; border-bottom: 2px solid #617de6; padding-bottom: 5px; margin-top: 20px;',
                          '#children' => 
                          array (
                            0 => 
                            array (
                              '#text' => 'Volunteers',
                            ),
                          ),
                        ),
                        2 => 
                        array (
                          '#text' => '
        
        
        ',
                        ),
                      ),
                    ),
                    58 => 
                    array (
                      '#text' => '
        ',
                    ),
                    59 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q25_volunteer_involvement',
                      'defn' => 
                      array (
                        'label' => '25. Volunteers are involved at the operational and governance levels of the organization.',
                      ),
                    ),
                    60 => 
                    array (
                      '#text' => '
        ',
                    ),
                    61 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q26_volunteer_job_descriptions',
                      'defn' => 
                      array (
                        'label' => '26. Every volunteer has a job description, an outline of their time commitment and any provided benefits, an identified supervisor, and they receive appropriate orientation and training aligned with their role. NOTE this may be as brief as a single paragraph if the volunteer position is episodic/short term.',
                      ),
                    ),
                    62 => 
                    array (
                      '#text' => '
        ',
                    ),
                    63 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q27_volunteer_screening',
                      'defn' => 
                      array (
                        'label' => '27. The organization has a screening policy and appropriately screens any volunteer with access to money or vulnerable people, including the requirement of a Police Records Check. NOTE – PRC is not required for every volunteer position, only those considered high risk.',
                      ),
                    ),
                    64 => 
                    array (
                      '#text' => '
        ',
                    ),
                    65 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q28_diverse_volunteer_cohort',
                      'defn' => 
                      array (
                        'label' => '28. The volunteer cohort is diverse and representative of the wider community.',
                      ),
                    ),
                    66 => 
                    array (
                      '#text' => '
        ',
                    ),
                    67 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q29_client_group_volunteers',
                      'defn' => 
                      array (
                        'label' => '29. Individuals representing the client group served by the organization have the opportunity to contribute and/or be a volunteer.',
                      ),
                    ),
                    68 => 
                    array (
                      '#text' => '
        ',
                    ),
                    69 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q30_volunteer_positions_effective',
                      'defn' => 
                      array (
                        'label' => '30. Volunteer positions are designed and allocated in the organization effectively and volunteers contribute to achieving the goals of the organization.',
                      ),
                    ),
                    70 => 
                    array (
                      '#text' => '
        ',
                    ),
                    71 => 
                    array (
                      '#tag' => 'div',
                      'class' => 'af-markup',
                      '#children' => 
                      array (
                        0 => 
                        array (
                          '#text' => '
          
          
          ',
                        ),
                        1 => 
                        array (
                          '#tag' => 'h4',
                          'style' => 'color: #617de6; border-bottom: 2px solid #617de6; padding-bottom: 5px; margin-top: 20px;',
                          '#children' => 
                          array (
                            0 => 
                            array (
                              '#text' => 'Communications & Fundraising',
                            ),
                          ),
                        ),
                        2 => 
                        array (
                          '#text' => '
        
        
        ',
                        ),
                      ),
                    ),
                    72 => 
                    array (
                      '#text' => '
        ',
                    ),
                    73 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q31_fundraising_strategy',
                      'defn' => 
                      array (
                        'label' => '31. The organization has an effective fundraising strategy, aligned with the annual Budget and related to the Mission.',
                      ),
                    ),
                    74 => 
                    array (
                      '#text' => '
        ',
                    ),
                    75 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q32_compelling_communications',
                      'defn' => 
                      array (
                        'label' => '32. The organization engages in compelling communications (marketing) with clients, donors, funders and/or the public, which aligns with the Vision and Mission.',
                      ),
                    ),
                    76 => 
                    array (
                      '#text' => '
        ',
                    ),
                    77 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q33_communication_guidelines',
                      'defn' => 
                      array (
                        'label' => '33. There are guidelines on who may speak for the organization as well as for use of social media to share information.',
                      ),
                    ),
                    78 => 
                    array (
                      '#text' => '
        ',
                    ),
                    79 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q34_website_technology',
                      'defn' => 
                      array (
                        'label' => '34. There is a good website, and the organization utilizes technology effectively within the context of the Mission and resources available.',
                      ),
                    ),
                    80 => 
                    array (
                      '#text' => '
        ',
                    ),
                    81 => 
                    array (
                      '#tag' => 'af-field',
                      'name' => 'Full_Self_Assessment_Survey.q35_positive_reputation',
                      'defn' => 
                      array (
                        'label' => '35. The organization has a good and positive reputation and is known for doing great work.',
                      ),
                    ),
                    82 => 
                    array (
                      '#text' => '
      ',
                    ),
                  ),
                ),
                4 => 
                array (
                  '#text' => '
    ',
                ),
              ),
            ),
            6 => 
            array (
              '#text' => '
    ',
            ),
            7 => 
            array (
              '#tag' => 'div',
              'class' => 'af-container af-layout-inline',
              '#children' => 
              array (
                0 => 
                array (
                  '#text' => '
      ',
                ),
                1 => 
                array (
                  '#tag' => 'button',
                  'class' => 'af-button btn btn-primary',
                  'crm-icon' => 'fa-check',
                  'ng-click' => 'afform.submit()',
                  'ng-if' => 'afform.showSubmitButton',
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#text' => 'Submit Survey',
                    ),
                  ),
                ),
                2 => 
                array (
                  '#text' => '
      ',
                ),
                3 => 
                array (
                  '#tag' => 'button',
                  'class' => 'af-button btn btn-primary',
                  'crm-icon' => 'fa-floppy-disk',
                  'ng-click' => 'afform.submitDraft()',
                  'ng-if' => 'afform.showSubmitButton',
                  '#children' => 
                  array (
                    0 => 
                    array (
                      '#text' => 'Save Draft',
                    ),
                  ),
                ),
                4 => 
                array (
                  '#text' => '
    ',
                ),
              ),
            ),
            8 => 
            array (
              '#text' => '
  ',
            ),
          ),
        ),
        8 => 
        array (
          '#text' => '
',
        ),
      ),
    ),
    1 => 
    array (
      '#text' => '
',
    ),
  ),
  'name' => 'afformMASSASF',
);
