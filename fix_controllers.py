#!/usr/bin/env python3
"""
Fix Symfony 7 / PHP 8.3 compatibility issues in controller files
"""
import os
import re
import sys

def fix_route_attributes(content):
    """Fix Route attributes to use PHP 8 syntax"""
    
    # Fix methods parameter: methods={"GET"} -> methods: ['GET']
    # Handle single methods with curly braces (with = sign!)
    content = re.sub(
        r'methods\s*=\s*\{\s*"([^"]+)"\s*\}',
        r"methods: ['\1']",
        content
    )
    
    # Handle multiple methods with comma separation in curly braces
    content = re.sub(
        r'methods\s*=\s*\{\s*"([^"]+)"\s*,\s*"([^"]+)"\s*\}',
        r"methods: ['\1', '\2']",
        content
    )
    
    # Handle methods with pipe (GET|POST) - split into array
    content = re.sub(
        r'methods\s*=\s*\{\s*"([^|"]+)\|([^"]+)"\s*\}',
        r"methods: ['\1', '\2']",
        content
    )
    
    # Fix methods with methods: string (non-array) to array - with pipe separator
    content = re.sub(
        r"methods:\s+'([^']+)\|([^']+)'",
        r"methods: ['\1', '\2']",
        content
    )
    content = re.sub(
        r'methods:\s+"([^"]+)\|([^"]+)"',
        r"methods: ['\1', '\2']",
        content
    )
    
    # Fix single method as string to array: methods: 'GET' -> methods: ['GET']
    content = re.sub(
        r"methods:\s+'([^'\|]+)'(?!\s*[,\]])",
        r"methods: ['\1']",
        content
    )
    content = re.sub(
        r'methods:\s+"([^"\|]+)"(?!\s*[,\]])',
        r"methods: ['\1']",
        content
    )
    
    # Fix requirements parameter: requirements={"page"="\d*"} -> requirements: ['page' => '\d*']
    # Handle escaped backslashes properly
    content = re.sub(
        r'requirements\s*=\s*\{\s*"([^"]+)"\s*=\s*"([^"]+)"\s*\}',
        lambda m: f"requirements: ['{m.group(1)}' => '{m.group(2)}']",
        content
    )
    
    # Fix requirements using colon syntax but with curly braces
    content = re.sub(
        r'requirements:\s*\{\s*"([^"]+)"\s*=\s*"([^"]+)"\s*\}',
        lambda m: f"requirements: ['{m.group(1)}' => '{m.group(2)}']",
        content
    )
    
    # Fix multiple requirements
    content = re.sub(
        r'requirements\s*=\s*\{\s*"([^"]+)"\s*=\s*"([^"]+)"\s*,\s*"([^"]+)"\s*=\s*"([^"]+)"\s*\}',
        lambda m: f"requirements: ['{m.group(1)}' => '{m.group(2)}', '{m.group(3)}' => '{m.group(4)}']",
        content
    )
    
    return content

def fix_symfony7_issues(filepath):
    """Fix a single controller file"""
    print(f"Processing: {filepath}")
    
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    original_content = content
    
    # Apply all fixes
    content = fix_route_attributes(content)
    
    # Write back if changes were made
    if content != original_content:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"  ✓ Fixed: {filepath}")
        return True
    else:
        print(f"  - No changes needed: {filepath}")
        return False

def main():
    controller_dir = '/home/werd/dev/lbook/src/Controller'
    
    if not os.path.exists(controller_dir):
        print(f"Error: Directory not found: {controller_dir}")
        sys.exit(1)
    
    # Get all PHP files
    php_files = []
    for filename in os.listdir(controller_dir):
        if filename.endswith('.php'):
            filepath = os.path.join(controller_dir, filename)
            php_files.append(filepath)
    
    print(f"Found {len(php_files)} PHP controller files\n")
    
    fixed_count = 0
    for filepath in sorted(php_files):
        if fix_symfony7_issues(filepath):
            fixed_count += 1
    
    print(f"\n{'='*60}")
    print(f"Summary: Fixed {fixed_count} out of {len(php_files)} files")
    print(f"{'='*60}")

if __name__ == '__main__':
    main()
