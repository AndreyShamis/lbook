#!/usr/bin/env python3
"""
Convert Symfony 4 annotations to Symfony 7 PHP attributes
"""
import os
import re
from pathlib import Path

def fix_doctrine_functions(file_path):
    """Fix Doctrine custom function signatures"""
    with open(file_path, 'r') as f:
        content = f.read()
    
    # Fix getSql return type
    content = re.sub(
        r'public function getSql\(\$sqlWalker\)',
        r'public function getSql($sqlWalker): string',
        content
    )
    
    # Fix parse method
    content = re.sub(
        r'public function parse\(\$parser\)',
        r'public function parse($parser): void',
        content
    )
    
    with open(file_path, 'w') as f:
        f.write(content)

def convert_route_annotation(content):
    """Convert @Route annotations to Route attributes"""
    # Find all @Route annotations
    pattern = r'/\*\*[\s\S]*?\*\s*@Route\((.*?)\)[\s\S]*?\*/'
    
    def replace_route(match):
        route_params = match.group(1)
        # Parse the route parameters
        route_params = route_params.strip()
        return f'#[Route({route_params})]'
    
    content = re.sub(pattern, replace_route, content)
    
    # Also handle single-line /** @Route() */ format
    content = re.sub(
        r'/\*\*\s*\n\s*\*\s*@Route\((.*?)\)\s*\n\s*\*/',
        r'#[Route(\1)]',
        content
    )
    
    return content

def convert_annotations_to_attributes(file_path):
    """Convert PHP annotations to attributes for Symfony 7"""
    with open(file_path, 'r') as f:
        content = f.read()
    
    original_content = content
    
    # Add missing use statements if needed
    if '@Route' in content and 'use Symfony\\Component\\Routing\\Annotation\\Route' in content:
        content = content.replace(
            'use Symfony\\Component\\Routing\\Annotation\\Route;',
            'use Symfony\\Component\\Routing\\Attribute\\Route;'
        )
    
    # Convert @Route to #[Route]
    content = convert_route_annotation(content)
    
    # Convert @Template to #[Template]
    if 'Template' in content:
        content = re.sub(
            r'/\*\*[\s\S]*?\*\s*@Template\((.*?)\)[\s\S]*?\*/',
            r'#[Template(\1)]',
            content
        )
        if 'use Sensio\\Bundle\\FrameworkExtraBundle\\Configuration\\Template' in content:
            content = content.replace(
                'use Sensio\\Bundle\\FrameworkExtraBundle\\Configuration\\Template;',
                'use Symfony\\Bridge\\Twig\\Attribute\\Template;'
            )
    
    # Remove /** ... */ doc blocks that only contain annotations
    content = re.sub(r'/\*\*\s*\n\s*\*\s*\n\s*\*/', '', content)
    
    # Fix getDoctrine() to use EntityManagerInterface
    if 'getDoctrine()' in content:
        # Add EntityManagerInterface to constructor if not exists
        if 'EntityManagerInterface' not in content:
            if 'namespace' in content:
                namespace_line = re.search(r'(namespace .*?;)', content).group(1)
                after_namespace = content.split(namespace_line, 1)[1]
                if 'use ' in after_namespace:
                    # Add after other use statements
                    last_use = after_namespace.rfind('use ')
                    line_end = after_namespace.find(';', last_use) + 1
                    position = content.find(namespace_line) + len(namespace_line) + line_end
                    content = (content[:position] + 
                              '\\nuse Doctrine\\ORM\\EntityManagerInterface;\\n' + 
                              content[position:])
    
    # Only write if changed
    if content != original_content:
        with open(file_path, 'w') as f:
            f.write(content)
        return True
    return False

def process_directory(directory):
    """Process all PHP files in directory"""
    changed_files = []
    
    # Fix Doctrine functions first
    doctrine_dir = Path(directory) / 'src' / 'Doctrine'
    if doctrine_dir.exists():
        for php_file in doctrine_dir.glob('*.php'):
            fix_doctrine_functions(php_file)
            changed_files.append(str(php_file))
    
    # Convert annotations in Controllers, Entities, etc.
    src_dir = Path(directory) / 'src'
    if src_dir.exists():
        for php_file in src_dir.rglob('*.php'):
            if php_file.name != '__init__.py':
                if convert_annotations_to_attributes(php_file):
                    changed_files.append(str(php_file))
    
    return changed_files

if __name__ == '__main__':
    project_dir = '/home/werd/dev/lbook'
    print(f"Processing {project_dir}...")
    changed = process_directory(project_dir)
    print(f"Modified {len(changed)} files")
    if changed:
        print("Sample of changed files:")
        for f in changed[:10]:
            print(f"  - {f}")
