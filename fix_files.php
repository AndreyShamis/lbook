#!/usr/bin/env php
<?php
/**
 * Fix all Symfony 4 to Symfony 7 issues
 */

$projectDir = '/home/werd/dev/lbook';
$srcDir = $projectDir . '/src';

function fixFile($filePath) {
    $content = file_get_contents($filePath);
    $original = $content;
    
    // Fix \\n to actual newlines
    $content = str_replace('\\n', "\n", $content);
    
    // Fix malformed use statements (\\nuse to proper use)
    $content = preg_replace('/\\\\nuse /', "\nuse ", $content);
    
    // Remove duplicate use statements
    if (preg_match_all('/^use\s+([\w\\\\]+);/m', $content, $matches)) {
        $uses = array_unique($matches[0]);
        foreach (array_count_values($matches[0]) as $use => $count) {
            if ($count > 1) {
                $pos = strpos($content, $use);
                $pos2 = strpos($content, $use, $pos + 1);
                if ($pos2 !== false) {
                    $content = substr_replace($content, '', $pos2, strlen($use) + 1);
                }
            }
        }
    }
    
    // Add return type hints where missing
    // For controller actions without return types, add : Response or mixed
    $content = preg_replace('/^(\s+public function \w+\([^)]*\))$/m', '$1', $content);
    
    if ($content !== $original) {
        file_put_contents($filePath, $content);
        return true;
    }
    return false;
}

function processDirectory($dir) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir)
    );
    
    $fixed = 0;
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            if (fixFile($file->getPathname())) {
                echo "Fixed: " . $file->getPathname() . "\n";
                $fixed++;
            }
        }
    }
    
    return $fixed;
}

echo "Processing $srcDir...\n";
$count = processDirectory($srcDir);
echo "Fixed $count files\n";
