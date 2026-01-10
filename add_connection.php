<?php

/**
 * Script to add $connection = 'client' to all models
 * Run with: php add_connection.php
 */

$modelsPath = __DIR__ . '/app/Models';
$models = glob($modelsPath . '/*.php');

echo "Adding \$connection = 'client' to client models...\n\n";

$updated = 0;
$skipped = 0;

foreach ($models as $modelFile) {
    $filename = basename($modelFile);
    $content = file_get_contents($modelFile);

    // Skip if already has connection
    if (strpos($content, "protected \$connection") !== false) {
        echo "SKIP: {$filename} (already has connection)\n";
        $skipped++;
        continue;
    }

    // Add connection after class declaration
    $pattern = '/class\s+\w+\s+extends\s+\w+\s*\{/';
    if (preg_match($pattern, $content, $matches)) {
        $replacement = $matches[0] . "\n    protected \$connection = 'client';";
        $newContent = preg_replace($pattern, $replacement, $content, 1);

        file_put_contents($modelFile, $newContent);
        echo "UPDATED: {$filename}\n";
        $updated++;
    } else {
        echo "ERROR: {$filename} (pattern not found)\n";
    }
}

echo "\n========================================\n";
echo "Results: {$updated} updated, {$skipped} skipped\n";
echo "========================================\n";
