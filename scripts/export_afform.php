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
 * 3. Export forms and blocks based on having "MAS" in their title
 * 4. Create .aff.json (metadata) and .aff.html (layout) files
 *
 * @author MAS Team
 * @version 2.0 (Simplified Block Detection)
 * @requires CiviCRM 6.1+, Afform extension
 */

echo "=== Afform Export Tool ===\n\n";

// CONFIGURATION
$FORM_TO_EXPORT = 'afformMASRCSForm';  // Change this to export different forms
$EXPORT_ALL = false;                    // Set to true to export all forms
$LIST_ONLY = false;                    // Set to true to just list available forms

// Get available forms and blocks with "MAS" in title
try {
    $forms = \Civi\Api4\Afform::get(false)
        ->addWhere('name', 'LIKE', '%MAS%')
        ->addSelect('name', 'title', 'type')
        ->execute();
} catch (Exception $e) {
    echo "Error fetching forms: " . $e->getMessage() . "\n";
    exit(1);
}

if (empty($forms)) {
    echo "No MAS forms found!\n";
    exit(1);
}

echo "Available MAS forms and blocks:\n";
foreach ($forms as $form) {
    echo "  - {$form['name']} ({$form['title']}) [{$form['type']}]\n";
}

// If just listing, exit here
if ($LIST_ONLY) {
    echo "\nTotal: " . count($forms) . " MAS forms/blocks available.\n";
    echo "To export, set \$LIST_ONLY = false in the script.\n";
    exit(0);
}

// Determine forms to export
$formsToExport = [];
if ($EXPORT_ALL) {
    echo "\nExporting ALL MAS forms and blocks...\n";
    foreach ($forms as $form) {
        $formsToExport[] = $form['name'];
    }
} else {
    echo "\nExporting form: {$FORM_TO_EXPORT}\n";
    $formFound = false;
    foreach ($forms as $form) {
        if ($form['name'] === $FORM_TO_EXPORT) {
            $formsToExport[] = $form['name'];
            $formFound = true;
            break;
        }
    }

    if (!$formFound) {
        echo "Error: Form '{$FORM_TO_EXPORT}' not found.\n";
        echo "Available forms are listed above.\n";
        exit(1);
    }
}

// Create export directory
$exportDir = \CRM_Mascode_ExtensionUtil::path('ang');
if (!is_dir($exportDir)) {
    mkdir($exportDir, 0755, true);
    echo "Created directory: $exportDir\n";
}

// Export each form
foreach ($formsToExport as $formName) {
    echo "\n--- Exporting Form: $formName ---\n";

    try {
        $form = \Civi\Api4\Afform::get(false)
            ->addWhere('name', '=', $formName)
            ->setCheckPermissions(false)
            ->execute()
            ->first();

        if (!$form) {
            echo "✗ Form not found: $formName\n";
            continue;
        }

        // Export form metadata
        $metadata = ['type' => $form['type'] ?? 'form'];

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

echo "\n=== Export Complete ===\n";
echo "Files saved to: $exportDir\n";

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

    // Handle text content
    if (isset($element['#text'])) {
        return htmlspecialchars($element['#text']);
    }

    // Handle markup content
    if (isset($element['#markup'])) {
        return $element['#markup'];
    }

    // Handle HTML tags
    if (isset($element['#tag'])) {
        return convertAfformTagToHtml($element);
    }

    // Handle children elements
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

    // Fallback - convert to comment
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
            // Convert attributes
            if (is_array($value)) {
                $attributes .= ' ' . $key . '="' . htmlspecialchars(json_encode($value)) . '"';
            } else {
                $attributes .= ' ' . $key . '="' . htmlspecialchars((string)$value) . '"';
            }
        }
    }

    // Handle self-closing tags
    $selfClosingTags = ['input', 'br', 'hr', 'img', 'meta', 'link'];
    if (in_array(strtolower($tag), $selfClosingTags)) {
        return "<{$tag}{$attributes} />";
    } else {
        return "<{$tag}{$attributes}>{$content}</{$tag}>";
    }
}
