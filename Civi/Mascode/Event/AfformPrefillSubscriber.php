<?php
// File: Civi/Mascode/Event/AfformPrefillSubscriber.php

namespace Civi\Mascode\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Civi\Afform\Event\AfformPrefillEvent;

class AfformPrefillSubscriber implements EventSubscriberInterface
{
    /**
     * @var \CRM_Utils_Token
     */
    protected $tokenProcessor;

    /**
     * Constructor with dependency initialization
     */
    public function __construct()
    {
        $this->tokenProcessor = new \CRM_Utils_Token();
        \Civi::log()->debug('FormPrefillSubscriber instantiated');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'civi.afform.prefill' => 'onFormPrefill',
        ];
    }

    /**
     * Pre-fill form data before display
     *
     * @param \Civi\Afform\Event\AfformPrefillEvent $event
     */
    public function onFormPrefill(AfformPrefillEvent $event): void
    {
        $formName = $event->getFormName();

        // Check if this is our target form
        if ($formName === 'mas_anonymous_case_form') {
            $this->prefillAnonymousCaseForm($event);
        }
    }

    /**
     * Prepopulate fields for anonymous case form with authorization check
     *
     * @param \Civi\Afform\Event\AfformPrefillEvent $event
     */
    protected function prefillAnonymousCaseForm(AfformPrefillEvent $event): void
    {
        try {
            $data = $event->getData();

            // Get URL parameters - this could be how you identify the case and validate access
            $checksum = $_GET['cs'] ?? NULL;
            $contactId = $_GET['cid'] ?? NULL;
            $caseId = $_GET['caseid'] ?? NULL;

            // Check if this is a valid anonymous access request
            if (!$this->validateAnonymousAccess($contactId, $checksum, $caseId)) {
                $data['access_denied'] = TRUE;
                $event->setData($data);
                return;
            }

            // If we get here, access is validated
            $data = $this->loadOrganizationData($data, $caseId);
            $data = $this->loadIndividualData($data, $contactId);
            $data = $this->loadCaseData($data, $caseId);

            $event->setData($data);
        } catch (\Exception $e) {
            \Civi::log()->error('Error in FormPrefillSubscriber::prefillAnonymousCaseForm: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
            
            // Set error state in form data
            $data = $event->getData();
            $data['error_occurred'] = TRUE;
            $event->setData($data);
        }
    }

    /**
     * Load organization data related to the case
     * 
     * @param array $data Form data
     * @param int $caseId Case ID
     * @return array Updated form data
     */
    protected function loadOrganizationData(array $data, int $caseId): array
    {
        try {
            // Assuming the organization is related to the case
            $orgContact = $this->getOrganizationForCase($caseId);
            if ($orgContact) {
                $data['organization'] = $data['organization'] ?? [[]];
                $data['organization'][0]['id'] = $orgContact['id'];
                $data['organization'][0]['display_name'] = $orgContact['display_name'];
                $data['organization'][0]['email'] = $orgContact['email.email'] ?? NULL;
                // Add more fields as needed
            }
        } catch (\Exception $e) {
            \Civi::log()->error('Failed to fetch organization data: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
        
        return $data;
    }

    /**
     * Load individual contact data
     * 
     * @param array $data Form data
     * @param int $contactId Contact ID
     * @return array Updated form data
     */
    protected function loadIndividualData(array $data, int $contactId): array
    {
        try {
            if ($contactId) {
                $individual = \Civi\Api4\Contact::get(FALSE)
                    ->addSelect('display_name', 'first_name', 'last_name', 'email.email')
                    ->addWhere('id', '=', $contactId)
                    ->addWhere('contact_type', '=', 'Individual')
                    ->execute()
                    ->first();

                if ($individual) {
                    $data['individual'] = $data['individual'] ?? [[]];
                    $data['individual'][0]['id'] = $individual['id'];
                    $data['individual'][0]['display_name'] = $individual['display_name'];
                    $data['individual'][0]['first_name'] = $individual['first_name'];
                    $data['individual'][0]['last_name'] = $individual['last_name'];
                    $data['individual'][0]['email'] = $individual['email.email'] ?? NULL;
                    // Add more fields as needed
                }
            }
        } catch (\Exception $e) {
            \Civi::log()->error('Failed to fetch individual data: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
        
        return $data;
    }

    /**
     * Load case data
     * 
     * @param array $data Form data
     * @param int $caseId Case ID
     * @return array Updated form data
     */
    protected function loadCaseData(array $data, int $caseId): array
    {
        try {
            if ($caseId) {
                $case = \Civi\Api4\CiviCase::get(FALSE)
                    ->addSelect('subject', 'case_type_id:label', 'status_id:label', 'start_date')
                    ->addWhere('id', '=', $caseId)
                    ->execute()
                    ->first();

                if ($case) {
                    $data['case'] = $data['case'] ?? [[]];
                    $data['case'][0]['id'] = $caseId;
                    $data['case'][0]['subject'] = $case['subject'];
                    $data['case'][0]['case_type_id:label'] = $case['case_type_id:label'];
                    $data['case'][0]['status_id:label'] = $case['status_id:label'];
                    $data['case'][0]['start_date'] = $case['start_date'];
                    // Add more fields as needed
                }
            }
        } catch (\Exception $e) {
            \Civi::log()->error('Failed to fetch case data: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
        
        return $data;
    }

    /**
     * Validate anonymous access to the form
     *
     * @param int|null $contactId
     * @param string|null $checksum
     * @param int|null $caseId
     * @return bool
     */
    protected function validateAnonymousAccess(?int $contactId, ?string $checksum, ?int $caseId): bool
    {
        // If we don't have the necessary parameters, deny access
        if (empty($contactId) || empty($checksum) || empty($caseId)) {
            \Civi::log()->info('Anonymous access denied: missing parameters');
            return FALSE;
        }

        // Validate the checksum for the contact
        $isValidChecksum = \CRM_Contact_BAO_Contact_Utils::validChecksum($contactId, $checksum);
        if (!$isValidChecksum) {
            \Civi::log()->info('Anonymous access denied: invalid checksum');
            return FALSE;
        }

        // Verify that this contact has access to this case
        try {
            $caseContact = \Civi\Api4\CaseContact::get(FALSE)
                ->addSelect('id')
                ->addWhere('case_id', '=', $caseId)
                ->addWhere('contact_id', '=', $contactId)
                ->execute();

            if ($caseContact->count() === 0) {
                // Contact is not associated with this case
                \Civi::log()->info('Anonymous access denied: contact not associated with case');
                return FALSE;
            }
        } catch (\Exception $e) {
            \Civi::log()->error('Failed to validate case access: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
            return FALSE;
        }

        \Civi::log()->info('Anonymous access granted for contact {contactId} to case {caseId}', [
            'contactId' => $contactId,
            'caseId' => $caseId,
        ]);
        return TRUE;
    }

    /**
     * Get organization contact associated with a case
     * 
     * @param int $caseId
     * @return array|null
     */
    protected function getOrganizationForCase(int $caseId): ?array
    {
        try {
            // There are several ways to determine the organization:

            // 1. Via relationship to case client
            $result = \Civi\Api4\Relationship::get(FALSE)
                ->addSelect('contact_id_b', 'contact_id_b.display_name', 'contact_id_b.email.email')
                ->addJoin('Contact AS contact_b', 'INNER', ['contact_id_b', '=', 'contact_b.id'])
                ->addWhere('contact_b.contact_type', '=', 'Organization')
                ->addWhere('case_id', '=', $caseId)
                ->addWhere('is_active', '=', TRUE)
                ->execute()
                ->first();

            if ($result) {
                return [
                    'id' => $result['contact_id_b'],
                    'display_name' => $result['contact_id_b.display_name'],
                    'email.email' => $result['contact_id_b.email.email'] ?? NULL,
                ];
            }

            // 2. Alternative: via custom field on case, if you store org reference there
            $customFieldId = 'custom_123'; // Replace with your actual custom field ID
            $result = \Civi\Api4\CiviCase::get(FALSE)
                ->addSelect($customFieldId)
                ->addWhere('id', '=', $caseId)
                ->execute()
                ->first();

            if (!empty($result[$customFieldId])) {
                $orgId = $result[$customFieldId];
                $org = \Civi\Api4\Contact::get(FALSE)
                    ->addSelect('display_name', 'email.email')
                    ->addWhere('id', '=', $orgId)
                    ->execute()
                    ->first();

                if ($org) {
                    return [
                        'id' => $orgId,
                        'display_name' => $org['display_name'],
                        'email.email' => $org['email.email'] ?? NULL,
                    ];
                }
            }
        } catch (\Exception $e) {
            \Civi::log()->error('Failed to fetch organization for case: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }

        return NULL;
    }
}