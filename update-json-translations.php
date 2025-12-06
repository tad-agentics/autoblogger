#!/usr/bin/env php
<?php
/**
 * Update JSON translation files with current build hash
 * 
 * WordPress wp_set_script_translations() looks for JSON files with specific hashes
 * that match the built JavaScript files. This script regenerates those files.
 */

// Get the build hash from the asset file
$assetFile = __DIR__ . '/assets/js/admin/build/index.asset.php';
if (!file_exists($assetFile)) {
    die("Error: Admin build asset file not found!\n");
}

$assetData = require $assetFile;
$buildHash = substr($assetData['version'], 0, 32); // WordPress uses first 32 chars

echo "Current build hash: $buildHash\n";

// Read the base Vietnamese translation file
$poFile = __DIR__ . '/languages/autoblogger-vi.po';
if (!file_exists($poFile)) {
    die("Error: Vietnamese .po file not found!\n");
}

// Parse PO file and convert to JSON format
$translations = [];
$currentMsgid = '';
$currentMsgstr = '';
$inMsgid = false;
$inMsgstr = false;

$lines = file($poFile);
foreach ($lines as $line) {
    $line = trim($line);
    
    if (strpos($line, 'msgid ') === 0) {
        // Save previous translation if exists
        if ($currentMsgid && $currentMsgstr) {
            $translations[$currentMsgid] = $currentMsgstr;
        }
        $currentMsgid = trim(substr($line, 6), '"');
        $currentMsgstr = '';
        $inMsgid = true;
        $inMsgstr = false;
    } elseif (strpos($line, 'msgstr ') === 0) {
        $currentMsgstr = trim(substr($line, 7), '"');
        $inMsgid = false;
        $inMsgstr = true;
    } elseif ($line && $line[0] === '"') {
        // Continuation line
        $value = trim($line, '"');
        if ($inMsgid) {
            $currentMsgid .= $value;
        } elseif ($inMsgstr) {
            $currentMsgstr .= $value;
        }
    } elseif (empty($line)) {
        // Empty line marks end of entry
        if ($currentMsgid && $currentMsgstr) {
            $translations[$currentMsgid] = $currentMsgstr;
        }
        $currentMsgid = '';
        $currentMsgstr = '';
        $inMsgid = false;
        $inMsgstr = false;
    }
}

// Save last translation
if ($currentMsgid && $currentMsgstr) {
    $translations[$currentMsgid] = $currentMsgstr;
}

// Remove empty translations
$translations = array_filter($translations, function($value) {
    return !empty($value);
});

echo "Found " . count($translations) . " translations\n";

// Create JSON structure matching WordPress format
$jsonData = [
    'domain' => 'autoblogger',
    'locale_data' => [
        'autoblogger' => array_merge(
            [''],
            $translations
        )
    ]
];

// Generate JSON files for both admin and editor builds
$builds = [
    'admin' => __DIR__ . '/assets/js/admin/build/index.asset.php',
    'editor' => __DIR__ . '/assets/js/editor/build/index.asset.php'
];

foreach ($builds as $name => $assetPath) {
    if (!file_exists($assetPath)) {
        echo "Warning: $name build not found, skipping\n";
        continue;
    }
    
    $asset = require $assetPath;
    $hash = substr($asset['version'], 0, 32);
    
    $jsonFile = __DIR__ . "/languages/autoblogger-vi_VN-$hash.json";
    
    if (file_put_contents($jsonFile, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        echo "✅ Created: $jsonFile\n";
    } else {
        echo "❌ Failed to create: $jsonFile\n";
    }
}

// Clean up old JSON files
$oldFiles = glob(__DIR__ . '/languages/autoblogger-vi_VN-*.json');
foreach ($oldFiles as $file) {
    $filename = basename($file);
    // Keep only files matching current build hashes
    $keep = false;
    foreach ($builds as $assetPath) {
        if (file_exists($assetPath)) {
            $asset = require $assetPath;
            $hash = substr($asset['version'], 0, 32);
            if (strpos($filename, $hash) !== false) {
                $keep = true;
                break;
            }
        }
    }
    
    if (!$keep) {
        unlink($file);
        echo "🗑️  Removed old: $filename\n";
    }
}

echo "\n✅ Translation files updated successfully!\n";

