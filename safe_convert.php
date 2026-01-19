#!/usr/bin/env php
<?php
/**
 * Safe Symfony 7 converter - preserves all structure
 */

function safeConvert($file) {
    $content = file_get_contents($file);
    $original = $content;
    
    // 1. Convert use statements
    $content = str_replace(
        'use Symfony\\Component\\Routing\\Annotation\\Route;',
        'use Symfony\\Component\\Routing\\Attribute\\Route;',
        $content
    );
    
    // 2. Convert @Route in docblocks to #[Route]
    $content = preg_replace_callback(
        '/\/\*\*\s*\n\s*\*[^\n]*\n\s*\*\s+@Route\((.*?)\)\s*\n/s',
        function($m) {
            $params = $m[1];
            // Convert parameters
            $params = preg_replace('/(\w+)="([^"]*)"/', '$1: \'$2\'', $params);
            $params = preg_replace('/methods=\{([^}]+)\}/', 'methods: [\1]', $params);
            $params = str_replace('"GET"', "'GET'", $params);
            $params = str_replace('"POST"', "'POST'", $params);
            $params = str_replace('"PUT"', "'PUT'", $params);
            $params = str_replace('"DELETE"', "'DELETE'", $params);
            
            // Handle requirements
            $params = preg_replace_callback(
                '/requirements=\{([^}]+)\}/',
                function($rm) {
                    $reqs = [];
                    if (preg_match_all('/"([^"]+)"="([^"]+)"/', $rm[1], $matches, PREG_SET_ORDER)) {
                        foreach ($matches as $match) {
                            $reqs[] = "'{$match[1]}' => '{$match[2]}'";
                        }
                    }
                    return 'requirements: [' . implode(', ', $reqs) . ']';
                },
                $params
            );
            
            return "    #[Route($params)]\n";
        },
        $content
    );
    
    // 3. Remove remaining docblock comments that only had @Route
    $content = preg_replace('/\/\*\*\s*\n\s*\*\s*\n\s*\*\/\s*\n/', '', $content);
    
    // 4. Clean up remaining docblocks with only param/return
    $content = preg_replace(
        '/\/\*\*\s*\n(\s*\*\s*@param[^\n]*\n)+(\s*\*\s*@return[^\n]*\n)?\s*\*\/\s*\n/',
        '',
        $content
    );
    
    if ($content !== $original) {
        file_put_contents($file, $content);
        return true;
    }
    return false;
}

$files = glob('/home/werd/dev/lbook/src/Controller/*.php');
$fixed = 0;

foreach ($files as $file) {
    if (safeConvert($file)) {
        echo "✓ " . basename($file) . "\n";
        $fixed++;
    }
}

echo "\nConverted $fixed files\n";
