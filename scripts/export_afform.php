<?php
// scripts/export_afform.php
// Usage: cv scr scripts/export_afform.php

echo "=== Afform Export Tool ===\n\n";

// Show available forms
$forms = \Civi\Api4\Afform::get()
    ->addWhere('type', '=', 'form')
    ->addWhere('name', 'NOT LIKE', 'civicrm%')
    ->addSelect('name', 'title')
    ->execute();

if ($forms->count() === 0) {
    echo "No custom forms found!\n";
    exit(1);
}

$formOptions = [];
$counter = 1;
foreach ($forms as $form) {
    $formOptions[$counter] = $form['name'];
    echo sprintf("%2d. %s - %s\n", $counter, $form['name'], $form['title']);
    $counter++;
}

echo "\nSelect form number (1-" . count($formOptions) . ") or 'all': ";
$input = trim(fgets(STDIN));

$formsToExport = [];
if (strtolower($input) === 'all') {
    $formsToExport = array_values($formOptions);
} elseif (is_numeric($input) && isset($formOptions[(int)$input])) {
    $formsToExport = [$formOptions[(int)$input]];
} else {
    echo "Invalid selection. Exiting.\n";
    exit(1);
}

// Create export directory
$exportDir = \CRM_Mascode_ExtensionUtil::path('ang');
if (!is_dir($exportDir)) {
    mkdir($exportDir, 0755, true);
    echo "Created directory: $exportDir\n";
}

// Export each form
foreach ($formsToExport as $formName) {
    echo "\n--- Exporting: $formName ---\n";
    
    try {
        $form = \Civi\Api4\Afform::get()
            ->addWhere('name', '=', $formName)
            ->setCheckPermissions(FALSE)
            ->execute()
            ->first();
        
        if (!$form) {
            echo "✗ Form not found: $formName\n";
            continue;
        }
        
        // Export metadata (.aff.json)
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
        
        $metadataFile = $exportDir . '/' . $formName . '.aff.json';
        file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo "✓ Metadata: " . basename($metadataFile) . "\n";
        
        // Export layout (.aff.html)
        if (isset($form['layout'])) {
            $layoutFile = $exportDir . '/' . $formName . '.aff.html';
            
            if (is_string($form['layout'])) {
                // Already HTML
                file_put_contents($layoutFile, $form['layout']);
                echo "✓ Layout (HTML): " . basename($layoutFile) . "\n";
            } elseif (is_array($form['layout'])) {
                // Convert array to HTML
                $html = convertAfformLayoutToHtml($form['layout']);
                file_put_contents($layoutFile, $html);
                echo "✓ Layout (converted): " . basename($layoutFile) . "\n";
                
                // Also save raw for debugging
                $rawFile = $exportDir . '/' . $formName . '.layout.debug.json';
                file_put_contents($rawFile, json_encode($form['layout'], JSON_PRETTY_PRINT));
                echo "  Debug layout: " . basename($rawFile) . "\n";
            } else {
                echo "! Unknown layout type: " . gettype($form['layout']) . "\n";
            }
        } else {
            echo "! No layout found\n";
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
function convertAfformLayoutToHtml($layout) {
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
function convertAfformElementToHtml($element) {
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
    
    // Fallback: convert to HTML comment for debugging
    return "<!-- Afform element: " . json_encode($element) . " -->\n";
}

/**
 * Convert Afform tag element to HTML
 */
function convertAfformTagToHtml($element) {
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
            // Regular HTML attribute
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
function convertAfformFieldToHtml($element) {
    $type = $element['#type'];
    
    // Handle common Afform field types
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
            // Generic field conversion
            $attributes = '';
            foreach ($element as $key => $value) {
                if ($key === '#type') continue;
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
