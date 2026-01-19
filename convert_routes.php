#!/usr/bin/env php
<?php

/**
 * Convert annotation-style route parameters to attribute-style
 * From: #[Route("/path", name="route_name", methods={"GET"})]
 * To:   #[Route('/path', name: 'route_name', methods: ['GET'])]
 */

function convertRouteAttributes($content) {
    // Pattern to match Route attributes
    $pattern = '/#\[Route\((.*?)\)\]/s';
    
    $content = preg_replace_callback($pattern, function($matches) {
        $params = $matches[1];
        
        // Convert name="value" to name: 'value'
        $params = preg_replace('/,?\s*(\w+)="([^"]*)"/', ', $1: \'$2\'', $params);
        
        // Convert name='value' to name: 'value'
        $params = preg_replace('/,?\s*(\w+)=\'([^\']*)\'/', ', $1: \'$2\'', $params);
        
        // Convert methods={"GET","POST"} to methods: ['GET', 'POST']
        $params = preg_replace('/methods=\{([^}]*)\}/', 'methods: [$1]', $params);
        
        // Convert double quotes in array to single quotes
        $params = preg_replace('/"(GET|POST|PUT|DELETE|PATCH|OPTIONS)"/', '\'$1\'', $params);
        
        // Convert first parameter if it doesn't have a key
        if (!preg_match('/^\s*["\']/', $params)) {
            // Already has proper format or no params
        } else {
            // First param is just a string without key, that's OK for path
        }
        
        // Clean up double commas
        $params = preg_replace('/,\s*,/', ',', $params);
        // Remove leading comma
        $params = preg_replace('/^\s*,/', '', $params);
        
        return '#[Route(' . $params . ')]';
    }, $content);
    
    return $content;
}

function processFile($filePath) {
    $content = file_get_contents($filePath);
    $original = $content;
    
    $content = convertRouteAttributes($content);
    
    if ($content !== $original) {
        file_put_contents($filePath, $content);
        return true;
    }
    return false;
}

$projectDir = '/home/werd/dev/lbook';
$srcDir = $projectDir . '/src';

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

echo "Fixed $fixed files\n";
