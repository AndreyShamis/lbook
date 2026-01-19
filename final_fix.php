#!/usr/bin/env php
<?php

/**
 * Final comprehensive fix for Symfony 7 conversion
 */

function fixRouteRequirements($content) {
    // Fix requirements from requirements={"key"="pattern"} to requirements: ['key' => 'pattern']
    $content = preg_replace_callback(
        '/requirements=\{([^}]+)\}/',
        function($matches) {
            $reqs = $matches[1];
            // Split by commas
            $parts = preg_split('/,\s*/', $reqs);
            $fixed = [];
            foreach ($parts as $part) {
                if (preg_match('/"([^"]+)"="([^"]+)"/', $part, $m)) {
                    $fixed[] = "'{$m[1]}' => '{$m[2]}'";
                } elseif (preg_match('/"([^"]+)"=\'([^\']+)\'/', $part, $m)) {
                    $fixed[] = "'{$m[1]}' => '{$m[2]}'";
                }
            }
            return 'requirements: [' . implode(', ', $fixed) . ']';
        },
        $content
    );
    
    return $content;
}

function fixTemplateAnnotations($content) {
    // Remove @Template annotations if using render()
    $content = preg_replace('/#\[Template\([^\]]*\)\]/', '', $content);
    return $content;
}

function removeStrayComments($content) {
    // Remove commented-out lines that may be breaking code
    $lines = explode("\n", $content);
    $fixed = [];
    $in_class = false;
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        
        // Track if we're inside a class
        if (preg_match('/^class\s+\w+/', $trimmed)) {
            $in_class = true;
        }
        
        // Skip lines that are ONLY comments in method position (these can break the parser)
        if ($in_class && preg_match('/^\/\/,\s*requirements=/', $trimmed)) {
            continue; // Skip this line
        }
        
        $fixed[] = $line;
    }
    
    return implode("\n", $fixed);
}

function processFile($filePath) {
    $content = file_get_contents($filePath);
    $original = $content;
    
    $content = fixRouteRequirements($content);
    $content = fixTemplateAnnotations($content);
    $content = removeStrayComments($content);
    
    if ($content !== $original) {
        file_put_contents($filePath, $content);
        return true;
    }
    return false;
}

$srcDir = '/home/werd/dev/lbook/src';

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($srcDir)
);

$fixed = 0;
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        if (processFile($file->getPathname())) {
            echo "Fixed: " . $file->getPathname() . "\n";
            $fixed++;
        }
    }
}

echo "\nFixed $fixed files\n";
echo "\nChecking for remaining syntax errors in controllers...\n";

// Run syntax check
$controller_dir = $srcDir . '/Controller';
$errors = 0;
foreach (new DirectoryIterator($controller_dir) as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        exec('php -l ' . escapeshellarg($file->getPathname()) . ' 2>&1', $output, $return);
        if ($return !== 0) {
            echo "ERROR in {$file->getFilename()}\n";
            $errors++;
        }
    }
}

if ($errors === 0) {
    echo "✓ All controllers have valid syntax!\n";
} else {
    echo "✗ Found $errors files with syntax errors\n";
}
