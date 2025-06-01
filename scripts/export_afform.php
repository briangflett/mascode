<?php

/**
 * Afform Export Tool
 *
 * Exports CiviCRM Afforms (FormBuilder forms) and their custom blocks for deployment
 * between development, staging, and production environments.
 *
 * USAGE:
 *   cv scr scripts/export_afform.php
 *
 * CONFIGURATION (edit the variables below):
 *
 * $FORM_TO_EXPORT:
 *   - Set to the name of a specific form to export (e.g., 'afformMASRCSForm')
 *   - Only used when $EXPORT_ALL is false
 *   - Form names are case-sensitive and must match exactly
 *
 * $EXPORT_ALL:
 *   - true:  Exports ALL custom forms (excludes core CiviCRM forms starting with 'civicrm')
 *   - false: Exports only the form specified in $FORM_TO_EXPORT
 *
 * $LIST_ONLY:
 *   - true:  Only lists available forms and exits (no export)
 *   - false: Normal export behavior
 *
 * BEHAVIOR:
 *
 * The script will:
 * 1. List all available custom forms for reference
 * 2. Export the specified form(s) to the 'ang/' directory in your extension
 * 3. Scan each form's layout for references to custom blocks
 * 4. Automatically export any custom blocks found
 * 5. Document block dependencies in the form's metadata
 *
 * OUTPUT FILES:
 *
 * For each exported form:
 *   - {formname}.aff.json  - Form metadata (title, permissions, dependencies, etc.)
 *   - {formname}.aff.html  - Form layout/structure
 *
 * For each custom block:
 *   - {blockname}.aff.json - Block metadata
 *   - {blockname}.aff.html - Block layout/structure
 *
 * EXAMPLES:
 *
 * List available forms only:
 *   $LIST_ONLY = true;
 *   Result: Shows all available forms and exits
 *
 * Export single form:
 *   $FORM_TO_EXPORT = 'afformMASRCSForm';
 *   $EXPORT_ALL = false;
 *   Result: Exports 'afformMASRCSForm' and any blocks it uses
 *
 * Export all custom forms:
 *   $EXPORT_ALL = true;
 *   Result: Exports every custom form and all their blocks
 *
 * NOTES:
 *
 * - Custom field sets are NOT exported (they're usually already in target environments)
 * - Only custom blocks are detected and exported (not core CiviCRM blocks)
 * - The script is safe to run multiple times (overwrites existing files)
 * - Works in non-interactive environments (production servers, CI/CD)
 *
 * ERROR HANDLING:
 *
 * - If a form is not found, it will be skipped with an error message
 * - If custom blocks are referenced but not found, they'll be skipped
 * - The script continues processing other forms/blocks if some fail
 *
 * @author MAS Team
 * @version 1.0
 * @requires CiviCRM 6.1+, Afform extension
 */


echo "=== Afform Export Tool ===\n\n";

// CONFIGURATION
$FORM_TO_EXPORT = 'afformMASRCSForm';  // Change this to export different forms
$EXPORT_ALL = false;                   // Set to true to export all forms
$LIST_ONLY = false;                    // Set to true to just list available forms

// Get available forms
try {
    $forms = \Civi\Api4\Afform::get()
        ->addWhere('type', '=', 'form')
        ->addWhere('name', 'NOT LIKE', 'civicrm%')
        ->addSelect('name', 'title')
        ->execute();
} catch (Exception $e) {
    echo "Error fetching forms: " . $e->getMessage() . "\n";
    exit(1);
}

if (empty($forms)) {
    echo "No custom forms found!\n";
    exit(1);
}

echo "Available forms:\n";
foreach ($forms as $form) {
    echo "  - {$form['name']} ({$form['title']})\n";
}

// If just listing, exit here
if ($LIST_ONLY) {
    echo "\nTotal: " . count($forms) . " custom forms available.\n";
    echo "To export, set \$LIST_ONLY = false in the script.\n";
    exit(0);
}

// Determine forms to export
$formsToExport = [];
if ($EXPORT_ALL) {
    echo "\nExporting ALL forms...\n";
    foreach ($forms as $form) {
        $formsToExport[] = $form['name'];
    }
} else {
    echo "\nExporting form: {$FORM_TO_EXPORT}\n";
    $formsToExport = [$FORM_TO_EXPORT];
}

// Create export directory
$exportDir = \CRM_Mascode_ExtensionUtil::path('ang');
if (!is_dir($exportDir)) {
    mkdir($exportDir, 0755, true);
    echo "Created directory: $exportDir\n";
}

$allBlocksToExport = [];

// Export each form
foreach ($formsToExport as $formName) {
    echo "\n--- Exporting Form: $formName ---\n";

    try {
        $form = \Civi\Api4\Afform::get()
            ->addWhere('name', '=', $formName)
            ->setCheckPermissions(false)
            ->execute()
            ->first();

        if (!$form) {
            echo "✗ Form not found: $formName\n";
            continue;
        }

        // Find custom blocks referenced in this form
        $referencedBlocks = findReferencedBlocks($form);
        if (!empty($referencedBlocks)) {
            echo "Found referenced blocks: " . implode(', ', $referencedBlocks) . "\n";
            $allBlocksToExport = array_merge($allBlocksToExport, $referencedBlocks);
        } else {
            echo "No custom blocks found in this form.\n";
        }

        // Export form metadata
        $metadata = ['type' => 'form'];

        $stringFields = ['title', 'description', 'server_route', 'redirect', 'base_module'];
        foreach ($stringFields as $field) {
            if (!empty($form[$field]) && is_string($form[$field])) {
                $metadata[$field] = $form[$field];
            }
        }

        $boolFields = ['is_public', 'is_dashlet', 'create_submission', 'submit_currently_open'];
        foreach ($boolFields as $field) {
            if (isset($form[$field])) {
                $metadata[$field] = (bool)$form[$field];
            }
        }

        if (isset($form['submit_limit']) && is_numeric($form['submit_limit'])) {
            $metadata['submit_limit'] = (int)$form['submit_limit'];
        }

        // Handle permission
        if (isset($form['permission'])) {
            if (is_array($form['permission'])) {
                $metadata['permission'] = $form['permission'];
            } elseif (is_string($form['permission'])) {
                $decoded = json_decode($form['permission'], true);
                $metadata['permission'] = is_array($decoded) ? $decoded : [$form['permission']];
            }
        }

        // Document block dependencies
        if (!empty($referencedBlocks)) {
            $metadata['requires'] = $referencedBlocks;
        }

        $metadataFile = $exportDir . '/' . $formName . '.aff.json';
        file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo "✓ Metadata: " . basename($metadataFile) . "\n";

        // Export layout
        if (isset($form['layout'])) {
            $layoutFile = $exportDir . '/' . $formName . '.aff.html';

            if (is_string($form['layout'])) {
                file_put_contents($layoutFile, $form['layout']);
                echo "✓ Layout (HTML): " . basename($layoutFile) . "\n";
            } elseif (is_array($form['layout'])) {
                $html = convertAfformLayoutToHtml($form['layout']);
                file_put_contents($layoutFile, $html);
                echo "✓ Layout (converted): " . basename($layoutFile) . "\n";
            }
        }

    } catch (Exception $e) {
        echo "✗ Error exporting $formName: " . $e->getMessage() . "\n";
    }
}

// Export custom blocks
if (!empty($allBlocksToExport)) {
    echo "\n--- Exporting Custom Blocks ---\n";

    $uniqueBlocks = array_unique($allBlocksToExport);

    foreach ($uniqueBlocks as $blockName) {
        echo "Exporting block: $blockName\n";

        try {
            $block = \Civi\Api4\Afform::get()
                ->addWhere('name', '=', $blockName)
                ->setCheckPermissions(false)
                ->execute()
                ->first();

            if (!$block) {
                echo "✗ Block not found: $blockName\n";
                continue;
            }

            // Export block metadata
            $blockMetadata = [
                'type' => $block['type'] ?? 'block',
                'title' => $block['title'] ?? $blockName,
            ];

            if (!empty($block['description'])) {
                $blockMetadata['description'] = $block['description'];
            }

            $blockMetadataFile = $exportDir . '/' . $blockName . '.aff.json';
            file_put_contents($blockMetadataFile, json_encode($blockMetadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            echo "✓ Block metadata: " . basename($blockMetadataFile) . "\n";

            // Export block layout
            if (isset($block['layout'])) {
                $blockLayoutFile = $exportDir . '/' . $blockName . '.aff.html';

                if (is_string($block['layout'])) {
                    file_put_contents($blockLayoutFile, $block['layout']);
                    echo "✓ Block layout: " . basename($blockLayoutFile) . "\n";
                } elseif (is_array($block['layout'])) {
                    $html = convertAfformLayoutToHtml($block['layout']);
                    file_put_contents($blockLayoutFile, $html);
                    echo "✓ Block layout (converted): " . basename($blockLayoutFile) . "\n";
                }
            }

        } catch (Exception $e) {
            echo "✗ Error exporting block $blockName: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "\nNo custom blocks to export.\n";
}

echo "\n=== Export Complete ===\n";
echo "Files saved to: $exportDir\n";

/**
 * Find custom blocks referenced in an Afform
 */
function findReferencedBlocks($afform)
{
    $blocks = [];

    if (empty($afform['layout'])) {
        return $blocks;
    }

    $layout = $afform['layout'];

    // Convert to string if it's an array
    if (is_array($layout)) {
        $layout = json_encode($layout);
    }

    // Look for custom block patterns (conservative approach)
    $patterns = [
        // Angular directive style: <af-my-custom-block>
        '/(?:<|\s)af-([a-z][a-z0-9-_]*block)[>\s]/i',
        // Component style that looks like blocks
        '/(?:<|\s)([a-z][a-z0-9-_]*-block)[>\s]/i',
        // JSON tag references to blocks
        '/"#tag":\s*"af-([^"]*block[^"]*)"/i',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $layout, $matches)) {
            foreach ($matches[1] as $match) {
                // Convert kebab-case to camelCase for block names
                $blockName = str_replace('-', '', ucwords($match, '-'));
                $blockName = lcfirst($blockName);

                // Only include if it really looks like a custom block
                if (strlen($blockName) > 5 && strpos($blockName, 'block') !== false) {
                    $blocks[] = $blockName;
                }
            }
        }
    }

    return array_unique($blocks);
}

/**
 * Convert Afform layout array to HTML string
 */
function convertAfformLayoutToHtml($layout)
{
    if (is_string($layout)) {
        return $layout;
    }

    if (!is_array($layout)) {
        return (string)$layout;
    }

    $html = '';

    foreach ($layout as $element) {
        if (is_string($element)) {
            $html .= $element;
        } elseif (is_array($element)) {
            $html .= convertAfformElementToHtml($element);
        }
    }

    return $html;
}

/**
 * Convert individual Afform element to HTML
 */
function convertAfformElementToHtml($element)
{
    if (is_string($element)) {
        return $element;
    }

    if (!is_array($element)) {
        return (string)$element;
    }

    // Handle different element types
    if (isset($element['#markup'])) {
        return $element['#markup'];
    }

    if (isset($element['#text'])) {
        return htmlspecialchars($element['#text']);
    }

    if (isset($element['#tag'])) {
        return convertAfformTagToHtml($element);
    }

    // Handle Angular/Afform specific elements
    if (isset($element['#children'])) {
        $html = '';
        if (is_array($element['#children'])) {
            foreach ($element['#children'] as $child) {
                $html .= convertAfformElementToHtml($child);
            }
        } else {
            $html = (string)$element['#children'];
        }
        return $html;
    }

    // Handle Afform field elements
    if (isset($element['#type'])) {
        return convertAfformFieldToHtml($element);
    }

    // Fallback
    return "<!-- Afform element: " . json_encode($element) . " -->\n";
}

/**
 * Convert Afform tag element to HTML
 */
function convertAfformTagToHtml($element)
{
    $tag = $element['#tag'];
    $attributes = '';
    $content = '';

    foreach ($element as $key => $value) {
        if ($key === '#tag') {
            continue;
        } elseif ($key === '#children') {
            if (is_array($value)) {
                foreach ($value as $child) {
                    $content .= convertAfformElementToHtml($child);
                }
            } else {
                $content .= (string)$value;
            }
        } elseif ($key === '#markup') {
            $content .= $value;
        } elseif (strpos($key, '#') !== 0) {
            if (is_array($value)) {
                $attributes .= ' ' . $key . '="' . htmlspecialchars(json_encode($value)) . '"';
            } else {
                $attributes .= ' ' . $key . '="' . htmlspecialchars((string)$value) . '"';
            }
        }
    }

    $selfClosingTags = ['input', 'br', 'hr', 'img', 'meta', 'link', 'area', 'base', 'col', 'embed', 'source', 'track', 'wbr'];
    if (in_array(strtolower($tag), $selfClosingTags)) {
        return "<{$tag}{$attributes} />";
    } else {
        return "<{$tag}{$attributes}>{$content}</{$tag}>";
    }
}

/**
 * Convert Afform field element to HTML
 */
function convertAfformFieldToHtml($element)
{
    $type = $element['#type'];

    switch ($type) {
        case 'af-field':
            $name = $element['name'] ?? 'unknown';
            return "<af-field name=\"{$name}\"></af-field>";

        case 'af-entity':
            $entityType = $element['type'] ?? 'Contact';
            $name = $element['name'] ?? 'entity';
            return "<af-entity type=\"{$entityType}\" name=\"{$name}\"></af-entity>";

        case 'fieldset':
            $content = '';
            if (isset($element['#children'])) {
                foreach ($element['#children'] as $child) {
                    $content .= convertAfformElementToHtml($child);
                }
            }
            return "<fieldset>{$content}</fieldset>";

        default:
            $attributes = '';
            foreach ($element as $key => $value) {
                if ($key === '#type') {
                    continue;
                }
                if (strpos($key, '#') !== 0) {
                    if (is_array($value)) {
                        $attributes .= ' ' . $key . '="' . htmlspecialchars(json_encode($value)) . '"';
                    } else {
                        $attributes .= ' ' . $key . '="' . htmlspecialchars((string)$value) . '"';
                    }
                }
            }
            return "<{$type}{$attributes}></{$type}>";
    }
}
