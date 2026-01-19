#!/usr/bin/env php
<?php

/**
 * Complete Symfony 4 to Symfony 7.2 Conversion Script
 * Converts all annotations to attributes and fixes syntax
 */

class Symfony7Converter
{
    private int $filesProcessed = 0;
    private int $filesModified = 0;
    
    public function convertFile(string $filePath): bool
    {
        $content = file_get_contents($filePath);
        $original = $content;
        
        // Apply all conversions
        $content = $this->convertUseStatements($content);
        $content = $this->convertRouteAnnotations($content);
        $content = $this->convertTemplateAnnotations($content);
        $content = $this->fixDoctrineSignatures($content);
        $content = $this->cleanupContent($content);
        
        $this->filesProcessed++;
        
        if ($content !== $original) {
            file_put_contents($filePath, $content);
            $this->filesModified++;
            return true;
        }
        
        return false;
    }
    
    private function convertUseStatements(string $content): string
    {
        // Convert Annotation to Attribute namespace
        $content = str_replace(
            'use Symfony\\Component\\Routing\\Annotation\\Route',
            'use Symfony\\Component\\Routing\\Attribute\\Route',
            $content
        );
        
        $content = str_replace(
            'use Sensio\\Bundle\\FrameworkExtraBundle\\Configuration\\Template',
            'use Symfony\\Bridge\\Twig\\Attribute\\Template',
            $content
        );
        
        return $content;
    }
    
    private function convertRouteAnnotations(string $content): string
    {
        // Convert /** @Route(...) */ docblocks to #[Route(...)] attributes
        $content = preg_replace_callback(
            '/\/\*\*[\s\S]*?\*\s*@Route\((.*?)\)[\s\S]*?\*\//m',
            function($matches) {
                return $this->convertRouteParams($matches[1]);
            },
            $content
        );
        
        // Fix existing #[Route] attributes
        $content = preg_replace_callback(
            '/#\[Route\((.*?)\)\]/s',
            function($matches) {
                return $this->convertRouteParams($matches[1]);
            },
            $content
        );
        
        return $content;
    }
    
    private function convertRouteParams(string $params): string
    {
        // Convert name="value" to name: 'value'
        $params = preg_replace('/(\w+)="([^"]*)"/', '$1: \'$2\'', $params);
        
        // Convert methods={"GET"} to methods: ['GET']
        $params = preg_replace('/methods=\{([^}]+)\}/', 'methods: [\1]', $params);
        $params = preg_replace('/"(GET|POST|PUT|DELETE|PATCH|OPTIONS|HEAD)"/', '\'$1\'', $params);
        
        // Convert requirements={"key"="pattern"} to requirements: ['key' => 'pattern']
        $params = preg_replace_callback(
            '/requirements=\{([^}]+)\}/',
            function($m) {
                $reqs = [];
                if (preg_match_all('/"([^"]+)"="([^"]+)"/', $m[1], $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $reqs[] = "'{$match[1]}' => '{$match[2]}'";
                    }
                }
                return 'requirements: [' . implode(', ', $reqs) . ']';
            },
            $params
        );
        
        // Clean up double commas and spaces
        $params = preg_replace('/,\s*,/', ',', $params);
        $params = preg_replace('/,\s+/', ', ', $params);
        
        // Handle first parameter (path) - keep it without key name
        $params = trim($params);
        if (!preg_match('/^["\']/', $params)) {
            $params = preg_replace('/^path:\s*/', '', $params);
        }
        
        return '#[Route(' . $params . ')]';
    }
    
    private function convertTemplateAnnotations(string $content): string
    {
        // Convert @Template() to #[Template()]
        $content = preg_replace(
            '/\/\*\*[\s\S]*?\*\s*@Template\((.*?)\)[\s\S]*?\*\//m',
            '#[Template($1)]',
            $content
        );
        
        return $content;
    }
    
    private function fixDoctrineSignatures(string $content): string
    {
        // Fix Doctrine custom function signatures for PHP 8+
        $content = preg_replace(
            '/public function getSql\(\$sqlWalker\)\s*(?!:)/',
            'public function getSql($sqlWalker): string',
            $content
        );
        
        $content = preg_replace(
            '/public function parse\(\$parser\)\s*(?!:)/',
            'public function parse($parser): void',
            $content
        );
        
        return $content;
    }
    
    private function cleanupContent(string $content): string
    {
        // Remove empty docblocks
        $content = preg_replace('/\/\*\*\s*\*\s*\*\/\s*\n/', '', $content);
        
        // Remove trailing whitespace
        $lines = explode("\n", $content);
        $lines = array_map('rtrim', $lines);
        $content = implode("\n", $lines);
        
        return $content;
    }
    
    public function processDirectory(string $dir): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                if ($this->convertFile($file->getPathname())) {
                    echo ".";
                }
            }
        }
    }
    
    public function getStats(): array
    {
        return [
            'processed' => $this->filesProcessed,
            'modified' => $this->filesModified,
        ];
    }
}

// Main execution
echo "Symfony 4 → Symfony 7.2 Converter\n";
echo "==================================\n\n";

$projectDir = '/home/werd/dev/lbook';
$srcDir = $projectDir . '/src';

$converter = new Symfony7Converter();

echo "Processing files";
$converter->processDirectory($srcDir);
echo "\n\n";

$stats = $converter->getStats();
echo "✓ Processed: {$stats['processed']} files\n";
echo "✓ Modified:  {$stats['modified']} files\n";

// Verify syntax
echo "\nVerifying PHP syntax...\n";
$errors = 0;
$controllerDir = $srcDir . '/Controller';

if (is_dir($controllerDir)) {
    foreach (new DirectoryIterator($controllerDir) as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            exec('php -l ' . escapeshellarg($file->getPathname()) . ' 2>&1', $output, $return);
            if ($return !== 0) {
                echo "✗ {$file->getFilename()}\n";
                $errors++;
            }
        }
    }
}

if ($errors === 0) {
    echo "✓ All controllers have valid syntax!\n";
} else {
    echo "✗ Found $errors files with syntax errors (manual fix may be needed)\n";
}

echo "\nConversion complete!\n";
