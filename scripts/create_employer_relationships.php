<?php

/**
 * Create Employer Relationships Based on Job Titles
 *
 * This script processes all existing individuals and creates appropriate
 * relationships to their employers based on their job titles.
 *
 * USAGE:
 *   cv scr scripts/create_employer_relationships.php
 *
 * CONFIGURATION (edit the variables below):
 *
 * $DRY_RUN:
 *   - true:  Only shows what relationships would be created (safe mode)
 *   - false: Actually creates the relationships in the database
 *
 * $LIMIT:
 *   - Set to a number to limit processing (e.g., 100 for testing)
 *   - Set to 0 or null for no limit (process all individuals)
 *
 * $VERBOSE:
 *   - true:  Shows detailed output for each individual processed
 *   - false: Only shows summary statistics
 *
 * BEHAVIOR:
 *
 * The script will:
 * 1. Find all individuals with job titles and current employer relationships
 * 2. Check if their job title matches specific patterns for President or Executive Director
 * 3. Create appropriate relationship types to their employer organization
 * 4. Skip individuals who already have the correct relationship
 * 5. Report statistics on relationships created
 *
 * JOB TITLE PATTERNS:
 *
 * President relationships created for:
 *   - "president" (case-insensitive)
 *   - "President"
 *
 * Executive Director relationships created for:
 *   - "executive director" (case-insensitive)
 *   - "Executive Director"
 *   - "Exec Director"
 *   - "Exec Dir"
 *   - "ED"
 *
 * RELATIONSHIP TYPES:
 *
 * The script uses CiviCRM relationship types:
 *   - "President of" relationship (Individual -> Organization)
 *   - "Executive Director of" relationship (Individual -> Organization)
 *
 * SAFETY FEATURES:
 *
 * - Dry run mode for safe testing
 * - Checks for existing relationships to avoid duplicates
 * - Validates that employer is an organization
 * - Comprehensive error handling and logging
 * - Processing limit option for large datasets
 *
 * EXAMPLES:
 *
 * Test run (safe):
 *   $DRY_RUN = true; $LIMIT = 10; $VERBOSE = true;
 *   Result: Shows what would happen for first 10 individuals
 *
 * Process first 50 individuals:
 *   $DRY_RUN = false; $LIMIT = 50; $VERBOSE = false;
 *   Result: Creates relationships for first 50 matching individuals
 *
 * Process all individuals:
 *   $DRY_RUN = false; $LIMIT = 0; $VERBOSE = false;
 *   Result: Processes entire database
 *
 * @author MAS Team
 * @version 1.0
 * @requires CiviCRM 6.1+, MAS Extension
 */

// scripts/create_employer_relationships.php
// Creates employer relationships based on job titles

echo "=== Create Employer Relationships Script ===\n\n";

// CONFIGURATION
$DRY_RUN = false;                                 // Set to false to actually create relationships
$LIMIT = 0;                                     // Set to 0 for no limit, or number to limit processing
$VERBOSE = false;                                 // Set to false for summary only

// Job title patterns for relationship types
$PRESIDENT_TITLES = [
    'president',
    'President'
];

$EXECUTIVE_DIRECTOR_TITLES = [
    'executive director',
    'Executive Director',
    'Exec Director',
    'Exec Dir',
    'ED'
];

// Get relationship type IDs
try {
    $relationshipTypes = \Civi\Api4\RelationshipType::get()
        ->addSelect('id', 'name_a_b', 'name_b_a')
        ->setCheckPermissions(false)
        ->execute();

    $presidentRelTypeId = null;
    $executiveDirectorRelTypeId = null;

    foreach ($relationshipTypes as $relType) {
        if ($relType['name_a_b'] === 'President of') {
            $presidentRelTypeId = $relType['id'];
        }
        if ($relType['name_a_b'] === 'Executive Director of') {
            $executiveDirectorRelTypeId = $relType['id'];
        }
    }

    if (!$presidentRelTypeId) {
        echo "Error: 'President of' relationship type not found!\n";
        echo "Available relationship types:\n";
        foreach ($relationshipTypes as $relType) {
            echo "  - {$relType['name_a_b']} (ID: {$relType['id']})\n";
        }
        exit(1);
    }

    if (!$executiveDirectorRelTypeId) {
        echo "Error: 'Executive Director of' relationship type not found!\n";
        echo "Available relationship types:\n";
        foreach ($relationshipTypes as $relType) {
            echo "  - {$relType['name_a_b']} (ID: {$relType['id']})\n";
        }
        exit(1);
    }

    echo "Found relationship types:\n";
    echo "  - President of: ID $presidentRelTypeId\n";
    echo "  - Executive Director of: ID $executiveDirectorRelTypeId\n\n";

} catch (Exception $e) {
    echo "Error getting relationship types: " . $e->getMessage() . "\n";
    exit(1);
}

// Get all individuals with employer relationships and job titles
try {
    $query = \Civi\Api4\Contact::get()
        ->addSelect('id', 'display_name', 'job_title')
        ->addWhere('contact_type', '=', 'Individual')
        ->addWhere('is_deleted', '=', false)
        ->addWhere('job_title', 'IS NOT NULL')
        ->addWhere('job_title', '!=', '')
        ->setCheckPermissions(false);

    if ($LIMIT > 0) {
        $query->setLimit($LIMIT);
    }

    $individuals = $query->execute();

    echo "Found " . count($individuals) . " individuals with job titles";
    if ($LIMIT > 0) {
        echo " (limited to $LIMIT)";
    }
    echo "\n\n";

} catch (Exception $e) {
    echo "Error getting individuals: " . $e->getMessage() . "\n";
    exit(1);
}

if ($DRY_RUN) {
    echo "=== DRY RUN MODE - No relationships will actually be created ===\n\n";
}

// Process each individual
$processedCount = 0;
$presidentCount = 0;
$executiveDirectorCount = 0;
$skippedCount = 0;
$errorCount = 0;

foreach ($individuals as $individual) {
    $processedCount++;
    $jobTitle = trim($individual['job_title']);
    $contactId = $individual['id'];
    $displayName = $individual['display_name'];

    if ($VERBOSE) {
        echo "Processing: $displayName (ID: $contactId, Job: $jobTitle)\n";
    }

    try {
        // Get current employer relationships
        $employerRels = \Civi\Api4\Relationship::get()
            ->addSelect('id', 'contact_id_b', 'relationship_type_id')
            ->addWhere('contact_id_a', '=', $contactId)
            ->addWhere('is_active', '=', true)
            ->addJoin('RelationshipType AS rel_type', 'INNER', ['relationship_type_id', '=', 'rel_type.id'])
            ->addWhere('rel_type.name_a_b', 'LIKE', '%Employee of%')
            ->setCheckPermissions(false)
            ->execute();

        if (empty($employerRels) || count($employerRels) === 0) {
            if ($VERBOSE) {
                echo "  ⚠ No employer relationship found, skipping\n";
            }
            $skippedCount++;
            continue;
        }

        $firstEmployerRel = $employerRels->first();
        if (!$firstEmployerRel) {
            if ($VERBOSE) {
                echo "  ⚠ No valid employer relationship found, skipping\n";
            }
            $skippedCount++;
            continue;
        }

        $employerId = $firstEmployerRel['contact_id_b'];

        // Verify employer is an organization
        $employer = \Civi\Api4\Contact::get()
            ->addSelect('id', 'display_name', 'contact_type')
            ->addWhere('id', '=', $employerId)
            ->setCheckPermissions(false)
            ->execute()
            ->first();

        if (!$employer || $employer['contact_type'] !== 'Organization') {
            if ($VERBOSE) {
                echo "  ⚠ Employer is not an organization, skipping\n";
            }
            $skippedCount++;
            continue;
        }

        // Determine if we should create a relationship
        $targetRelTypeId = null;
        $relTypeName = '';

        // Check for President titles
        foreach ($PRESIDENT_TITLES as $title) {
            if (strcasecmp($jobTitle, $title) === 0) {
                $targetRelTypeId = $presidentRelTypeId;
                $relTypeName = 'President of';
                break;
            }
        }

        // Check for Executive Director titles
        if (!$targetRelTypeId) {
            foreach ($EXECUTIVE_DIRECTOR_TITLES as $title) {
                if (strcasecmp($jobTitle, $title) === 0) {
                    $targetRelTypeId = $executiveDirectorRelTypeId;
                    $relTypeName = 'Executive Director of';
                    break;
                }
            }
        }

        if (!$targetRelTypeId) {
            if ($VERBOSE) {
                echo "  - Job title doesn't match patterns, skipping\n";
            }
            $skippedCount++;
            continue;
        }

        // Check if relationship already exists
        $existingRel = \Civi\Api4\Relationship::get()
            ->addWhere('contact_id_a', '=', $contactId)
            ->addWhere('contact_id_b', '=', $employerId)
            ->addWhere('relationship_type_id', '=', $targetRelTypeId)
            ->addWhere('is_active', '=', true)
            ->setCheckPermissions(false)
            ->execute()
            ->first();

        if ($existingRel) {
            if ($VERBOSE) {
                echo "  ✓ $relTypeName relationship already exists, skipping\n";
            }
            $skippedCount++;
            continue;
        }

        // Create the relationship
        if ($DRY_RUN) {
            echo "  [DRY RUN] Would create: $relTypeName relationship to {$employer['display_name']}\n";
        } else {
            \Civi\Api4\Relationship::create()
                ->addValue('contact_id_a', $contactId)
                ->addValue('contact_id_b', $employerId)
                ->addValue('relationship_type_id', $targetRelTypeId)
                ->addValue('is_active', true)
                ->setCheckPermissions(false)
                ->execute();

            if ($VERBOSE) {
                echo "  ✓ Created: $relTypeName relationship to {$employer['display_name']}\n";
            }
        }

        if ($targetRelTypeId === $presidentRelTypeId) {
            $presidentCount++;
        } else {
            $executiveDirectorCount++;
        }

    } catch (Exception $e) {
        echo "  ✗ Error processing $displayName: " . $e->getMessage() . "\n";
        $errorCount++;
    }

    if ($VERBOSE) {
        echo "\n";
    }
}

// Summary
echo "=== Processing Complete ===\n";
echo "Individuals processed: $processedCount\n";
echo "President relationships " . ($DRY_RUN ? 'identified' : 'created') . ": $presidentCount\n";
echo "Executive Director relationships " . ($DRY_RUN ? 'identified' : 'created') . ": $executiveDirectorCount\n";
echo "Skipped: $skippedCount\n";
echo "Errors: $errorCount\n";

if ($DRY_RUN) {
    echo "\n✓ Dry run complete! Set \$DRY_RUN = false to actually create relationships.\n";
} else {
    echo "\n✓ Relationship creation complete!\n";
}
