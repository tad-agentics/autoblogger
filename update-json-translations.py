#!/usr/bin/env python3
"""
Update JSON translation files with current build hash

WordPress wp_set_script_translations() looks for JSON files with specific hashes
that match the built JavaScript files. This script regenerates those files.
"""

import json
import os
import re
import glob

# Get the build hash from the asset file
asset_file = 'assets/js/admin/build/index.asset.php'
if not os.path.exists(asset_file):
    print("Error: Admin build asset file not found!")
    exit(1)

with open(asset_file, 'r') as f:
    content = f.read()
    match = re.search(r"'version' => '([a-f0-9]+)'", content)
    if match:
        build_hash = match.group(1)[:32]  # WordPress uses first 32 chars
        print(f"Current build hash: {build_hash}")
    else:
        print("Error: Could not extract build hash!")
        exit(1)

# Read the base Vietnamese translation file
po_file = 'languages/autoblogger-vi.po'
if not os.path.exists(po_file):
    print("Error: Vietnamese .po file not found!")
    exit(1)

# Parse PO file and convert to JSON format
translations = {}
current_msgid = ''
current_msgstr = ''
in_msgid = False
in_msgstr = False

with open(po_file, 'r', encoding='utf-8') as f:
    for line in f:
        line = line.strip()
        
        if line.startswith('msgid '):
            # Save previous translation if exists
            if current_msgid and current_msgstr:
                translations[current_msgid] = current_msgstr
            current_msgid = line[6:].strip('"')
            current_msgstr = ''
            in_msgid = True
            in_msgstr = False
        elif line.startswith('msgstr '):
            current_msgstr = line[7:].strip('"')
            in_msgid = False
            in_msgstr = True
        elif line and line[0] == '"':
            # Continuation line
            value = line.strip('"')
            if in_msgid:
                current_msgid += value
            elif in_msgstr:
                current_msgstr += value
        elif not line:
            # Empty line marks end of entry
            if current_msgid and current_msgstr:
                translations[current_msgid] = current_msgstr
            current_msgid = ''
            current_msgstr = ''
            in_msgid = False
            in_msgstr = False

# Save last translation
if current_msgid and current_msgstr:
    translations[current_msgid] = current_msgstr

# Remove empty translations
translations = {k: v for k, v in translations.items() if v}

print(f"Found {len(translations)} translations")

# Create JSON structure matching WordPress format
json_data = {
    'domain': 'autoblogger',
    'locale_data': {
        'autoblogger': {
            '': {
                'domain': 'autoblogger',
                'lang': 'vi_VN'
            },
            **translations
        }
    }
}

# Generate JSON files for both admin and editor builds
builds = {
    'admin': 'assets/js/admin/build/index.asset.php',
    'editor': 'assets/js/editor/build/index.asset.php'
}

created_files = []
for name, asset_path in builds.items():
    if not os.path.exists(asset_path):
        print(f"Warning: {name} build not found, skipping")
        continue
    
    with open(asset_path, 'r') as f:
        content = f.read()
        match = re.search(r"'version' => '([a-f0-9]+)'", content)
        if match:
            hash_val = match.group(1)[:32]
        else:
            continue
    
    json_file = f"languages/autoblogger-vi_VN-{hash_val}.json"
    
    with open(json_file, 'w', encoding='utf-8') as f:
        json.dump(json_data, f, ensure_ascii=False, indent=2)
    print(f"✅ Created: {json_file}")
    created_files.append(os.path.basename(json_file))

# Clean up old JSON files
old_files = glob.glob('languages/autoblogger-vi_VN-*.json')
for file in old_files:
    filename = os.path.basename(file)
    if filename not in created_files:
        os.unlink(file)
        print(f"🗑️  Removed old: {filename}")

print("\n✅ Translation files updated successfully!")

