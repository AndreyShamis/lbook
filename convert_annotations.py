#!/usr/bin/env python3
"""
Convert Symfony 4 annotation syntax to Symfony 7 attribute syntax
"""
import os
import re
from pathlib import Path
from typing import List, Dict, Tuple

class AnnotationConverter:
    def __init__(self):
        self.stats = {
            'files_processed': 0,
            'files_modified': 0,
            'conversions': 0,
            'errors': []
        }
    
    def convert_file(self, filepath: str) -> bool:
        """Convert a single PHP file from annotations to attributes"""
        try:
            with open(filepath, 'r', encoding='utf-8') as f:
                content = f.read()
            
            original_content = content
            self.stats['files_processed'] += 1
            
            # Convert the content
            content = self.convert_use_statements(content)
            content = self.convert_class_annotations(content)
            content = self.convert_property_annotations(content)
            content = self.convert_method_annotations(content)
            
            # Only write if content changed
            if content != original_content:
                with open(filepath, 'w', encoding='utf-8') as f:
                    f.write(content)
                self.stats['files_modified'] += 1
                return True
            
            return False
            
        except Exception as e:
            self.stats['errors'].append(f"{filepath}: {str(e)}")
            return False
    
    def convert_use_statements(self, content: str) -> str:
        """Convert use statements"""
        # Replace Route annotation with attribute
        content = content.replace(
            'use Symfony\\Component\\Routing\\Annotation\\Route;',
            'use Symfony\\Component\\Routing\\Attribute\\Route;'
        )
        
        # Remove Template annotation import
        lines = content.split('\n')
        new_lines = []
        for line in lines:
            if 'use Sensio\\Bundle\\FrameworkExtraBundle\\Configuration' in line:
                continue
            new_lines.append(line)
        content = '\n'.join(new_lines)
        
        return content
    
    def convert_class_annotations(self, content: str) -> str:
        """Convert class-level annotations to attributes"""
        
        # Convert @Route at class level (multi-line or single-line)
        def replace_class_route(match):
            route_content = match.group(1)
            # Clean up the annotation content
            route_content = route_content.strip()
            # Convert to attribute syntax
            result = f"#[Route({route_content})]"
            self.stats['conversions'] += 1
            return result
        
        # Match class-level @Route (before "class" keyword)
        content = re.sub(
            r'/\*\*\s*\n\s*\*[^\n]*\n\s*\*\s*@Route\(((?:[^)]*|\([^)]*\))*)\)\s*\n\s*\*/\s*\nclass',
            lambda m: replace_class_route(m) + '\nclass',
            content
        )
        
        # Convert @ORM\Entity
        def replace_entity(match):
            entity_content = match.group(1) if match.group(1) else ''
            if entity_content:
                # Convert repositoryClass="App\Repository\XRepository" to repositoryClass: XRepository::class
                if 'repositoryClass=' in entity_content:
                    entity_content = entity_content.replace('repositoryClass="App\\Repository\\', 'repositoryClass: ')
                    # Find the closing quote and replace with ::class
                    entity_content = entity_content.replace('"', '::class', 1) if 'repositoryClass:' in entity_content else entity_content
                result = f"#[ORM\\Entity({entity_content})]"
            else:
                result = "#[ORM\\Entity]"
            self.stats['conversions'] += 1
            return result
        
        content = re.sub(
            r'@ORM\\Entity(?:\(((?:[^)]*|\([^)]*\))*)\))?',
            replace_entity,
            content
        )
        
        # Convert @ORM\Table with complex options
        def replace_table(match):
            table_content = match.group(1)
            # Keep the content mostly as-is but convert to named parameters
            table_content = re.sub(r'(\w+)=', r'\1: ', table_content)
            result = f"#[ORM\\Table({table_content})]"
            self.stats['conversions'] += 1
            return result
        
        content = re.sub(
            r'@ORM\\Table\(((?:[^)]*|\([^)]*\)|\{[^}]*\})*)\)',
            replace_table,
            content,
            flags=re.DOTALL
        )
        
        return content
    
    def convert_property_annotations(self, content: str) -> str:
        """Convert property-level annotations to attributes"""
        
        # Convert @ORM\Id
        content = content.replace('@ORM\\Id()', '#[ORM\\Id]')
        
        # Convert @ORM\GeneratedValue
        def replace_generated_value(match):
            strategy = match.group(1) if match.group(1) else ''
            if strategy:
                result = f"#[ORM\\GeneratedValue({strategy})]"
            else:
                result = "#[ORM\\GeneratedValue]"
            self.stats['conversions'] += 1
            return result
        
        content = re.sub(
            r'@ORM\\GeneratedValue(?:\(((?:[^)]*|\([^)]*\))*)\))?',
            replace_generated_value,
            content
        )
        
        # Convert @ORM\Column
        def replace_column(match):
            column_content = match.group(1)
            # Convert = to : for named parameters
            column_content = re.sub(r'(\w+)=', r'\1: ', column_content)
            result = f"#[ORM\\Column({column_content})]"
            self.stats['conversions'] += 1
            return result
        
        content = re.sub(
            r'@ORM\\Column\(((?:[^)]*|\([^)]*\)|\{[^}]*\})*)\)',
            replace_column,
            content,
            flags=re.DOTALL
        )
        
        # Convert @ORM\ManyToOne
        def replace_many_to_one(match):
            relation_content = match.group(1)
            # Convert targetEntity="App\Entity\X" to targetEntity: X::class
            relation_content = relation_content.replace('targetEntity="App\\Entity\\', 'targetEntity: ')
            relation_content = relation_content.replace('"', '::class', 1) if 'targetEntity:' in relation_content else relation_content
            relation_content = re.sub(r'(\w+)=', r'\1: ', relation_content)
            result = f"#[ORM\\ManyToOne({relation_content})]"
            self.stats['conversions'] += 1
            return result
        
        content = re.sub(
            r'@ORM\\ManyToOne\(((?:[^)]*|\([^)]*\))*)\)',
            replace_many_to_one,
            content
        )
        
        # Convert @ORM\OneToMany
        def replace_one_to_many(match):
            relation_content = match.group(1)
            relation_content = relation_content.replace('targetEntity="App\\Entity\\', 'targetEntity: ')
            relation_content = relation_content.replace('"', '::class', 1) if 'targetEntity:' in relation_content else relation_content
            relation_content = re.sub(r'(\w+)=', r'\1: ', relation_content)
            result = f"#[ORM\\OneToMany({relation_content})]"
            self.stats['conversions'] += 1
            return result
        
        content = re.sub(
            r'@ORM\\OneToMany\(((?:[^)]*|\([^)]*\))*)\)',
            replace_one_to_many,
            content
        )
        
        # Convert @ORM\ManyToMany
        def replace_many_to_many(match):
            relation_content = match.group(1)
            relation_content = relation_content.replace('targetEntity="App\\Entity\\', 'targetEntity: ')
            relation_content = relation_content.replace('"', '::class', 1) if 'targetEntity:' in relation_content else relation_content
            relation_content = re.sub(r'(\w+)=', r'\1: ', relation_content)
            result = f"#[ORM\\ManyToMany({relation_content})]"
            self.stats['conversions'] += 1
            return result
        
        content = re.sub(
            r'@ORM\\ManyToMany\(((?:[^)]*|\([^)]*\))*)\)',
            replace_many_to_many,
            content
        )
        
        # Convert @ORM\OneToOne
        def replace_one_to_one(match):
            relation_content = match.group(1) if match.group(1) else ''
            if relation_content:
                relation_content = relation_content.replace('targetEntity="App\\Entity\\', 'targetEntity: ')
                relation_content = relation_content.replace('"', '::class', 1) if 'targetEntity:' in relation_content else relation_content
                relation_content = re.sub(r'(\w+)=', r'\1: ', relation_content)
                result = f"#[ORM\\OneToOne({relation_content})]"
            else:
                result = "#[ORM\\OneToOne]"
            self.stats['conversions'] += 1
            return result
        
        content = re.sub(
            r'@ORM\\OneToOne(?:\(((?:[^)]*|\([^)]*\)|\{[^}]*\})*)\))?',
            replace_one_to_one,
            content
        )
        
        # Convert @ORM\JoinColumn
        def replace_join_column(match):
            join_content = match.group(1) if match.group(1) else ''
            if join_content:
                join_content = re.sub(r'(\w+)=', r'\1: ', join_content)
                result = f"#[ORM\\JoinColumn({join_content})]"
            else:
                result = "#[ORM\\JoinColumn]"
            self.stats['conversions'] += 1
            return result
        
        content = re.sub(
            r'@ORM\\JoinColumn(?:\(((?:[^)]*|\([^)]*\))*)\))?',
            replace_join_column,
            content
        )
        
        # Convert @ORM\JoinTable
        def replace_join_table(match):
            join_content = match.group(1) if match.group(1) else ''
            if join_content:
                join_content = re.sub(r'(\w+)=', r'\1: ', join_content)
                result = f"#[ORM\\JoinTable({join_content})]"
            else:
                result = "#[ORM\\JoinTable]"
            self.stats['conversions'] += 1
            return result
        
        content = re.sub(
            r'@ORM\\JoinTable(?:\(((?:[^)]*|\([^)]*\)|\{[^}]*\})*)\))?',
            replace_join_table,
            content,
            flags=re.DOTALL
        )
        
        # Convert @ORM\OrderBy
        def replace_order_by(match):
            order_content = match.group(1)
            result = f"#[ORM\\OrderBy({order_content})]"
            self.stats['conversions'] += 1
            return result
        
        content = re.sub(
            r'@ORM\\OrderBy\(((?:[^)]*|\{[^}]*\})*)\)',
            replace_order_by,
            content
        )
        
        return content
    
    def convert_method_annotations(self, content: str) -> str:
        """Convert method-level annotations to attributes"""
        
        # Convert @Route at method level
        def replace_method_route(match):
            route_content = match.group(1)
            # Convert = to : for named parameters
            route_content = re.sub(r'(\w+)=', r'\1: ', route_content)
            # Convert {"GET"} to ["GET"]
            route_content = re.sub(r'\{([^}]+)\}', r'[\1]', route_content)
            result = f"#[Route({route_content})]"
            self.stats['conversions'] += 1
            return result
        
        content = re.sub(
            r'@Route\(((?:[^)]*|\([^)]*\)|\{[^}]*\})*)\)',
            replace_method_route,
            content
        )
        
        # Convert @PrePersist, @PreUpdate, @PreFlush, @PostLoad etc.
        lifecycle_callbacks = [
            'PrePersist', 'PostPersist', 'PreUpdate', 'PostUpdate',
            'PreRemove', 'PostRemove', 'PreFlush', 'PostFlush', 'PostLoad'
        ]
        
        for callback in lifecycle_callbacks:
            content = re.sub(
                f'@ORM\\{callback}',
                f'#[ORM\\{callback}]',
                content
            )
            # Count conversions
            if f'#[ORM\\{callback}]' in content:
                self.stats['conversions'] += 1
        
        # Remove @Template annotations (deprecated)
        content = re.sub(r'\s*\*\s*@Template\([^)]*\)\s*\n', '', content)
        
        return content
    
    def process_directory(self, directory: str):
        """Process all PHP files in a directory"""
        path = Path(directory)
        if not path.exists():
            self.stats['errors'].append(f"Directory not found: {directory}")
            return
        
        for php_file in path.glob('*.php'):
            self.convert_file(str(php_file))
    
    def print_summary(self):
        """Print conversion summary"""
        print("\n" + "="*60)
        print("CONVERSION SUMMARY")
        print("="*60)
        print(f"Files processed: {self.stats['files_processed']}")
        print(f"Files modified: {self.stats['files_modified']}")
        print(f"Total conversions: {self.stats['conversions']}")
        
        if self.stats['errors']:
            print(f"\nErrors encountered: {len(self.stats['errors'])}")
            for error in self.stats['errors']:
                print(f"  - {error}")
        else:
            print("\nNo errors encountered ✓")
        print("="*60)


def main():
    converter = AnnotationConverter()
    
    # Base directory
    base_dir = "/home/werd/dev/lbook/src"
    
    # Process all specified directories
    directories = [
        f"{base_dir}/Controller",
        f"{base_dir}/Entity",
        f"{base_dir}/Repository",
        f"{base_dir}/Form",
    ]
    
    print("Starting conversion from Symfony 4 annotations to Symfony 7 attributes...")
    print(f"Processing directories: {', '.join(directories)}\n")
    
    for directory in directories:
        print(f"Processing: {directory}")
        converter.process_directory(directory)
    
    converter.print_summary()


if __name__ == "__main__":
    main()
