"""Turns web/lib/labItems.ts into mobile/lib/game/lab_items.dart, so both clients
show the same icon and words for every item, zone and colour."""
import re, sys, os

root = sys.argv[1]
ts = open(os.path.join(root, 'web/lib/labItems.ts'), encoding='utf-8').read()

def block(name):
    m = re.search(r'const ' + name + r'[^=]*=\s*\{(.*?)\n\};', ts, re.S)
    return m.group(1)

def dq(s):
    return "'" + s.replace('\\', '\\\\').replace("'", "\\'").replace('$', '\\$') + "'"

items = re.findall(r'(\w+):\s*{\s*emoji:\s*"([^"]*)",\s*label:\s*"([^"]*)"\s*}', block('ITEMS'))
places = re.findall(r'(\w+):\s*"([^"]*)"', block('PLACES'))
colours = re.findall(r'(\w+):\s*"#([0-9A-Fa-f]{6})"', block('COLOURS'))

out = ["// GENERATED from web/lib/labItems.ts by the brand/items script; edit that file, not this one.",
       "// docs/experiment-engine.md: every item id maps to one icon and label in every client.",
       "", "import 'package:flutter/material.dart';", "",
       "class LabItem {", "  const LabItem(this.emoji, this.label);", "  final String emoji;", "  final String label;", "}", "",
       "const Map<String, LabItem> kItems = {"]
out += [f"  {dq(k)}: LabItem({dq(e)}, {dq(l)})," for k, e, l in items]
out += ["};", "", "const Map<String, String> kPlaces = {"]
out += [f"  {dq(k)}: {dq(v)}," for k, v in places]
out += ["};", "", "const Map<String, Color> kColours = {"]
out += [f"  {dq(k)}: Color(0xFF{h.upper()})," for k, h in colours]
out += ["};", "",
        "String _words(String id) {",
        "  final s = id.replaceAll('_', ' ');",
        "  return s.isEmpty ? s : s[0].toUpperCase() + s.substring(1);",
        "}", "",
        "LabItem item(String id) => kItems[id] ?? LabItem('🧪', _words(id));",
        "String place(String id) => kPlaces[id] ?? _words(id);",
        "Color colour(String name) => kColours[name] ?? const Color(0xFFE8F4FD);", ""]
dst = os.path.join(root, 'mobile/lib/game/lab_items.dart')
os.makedirs(os.path.dirname(dst), exist_ok=True)
open(dst, 'w', encoding='utf-8', newline='\n').write('\n'.join(out))
print(len(items), 'items', len(places), 'places', len(colours), 'colours')
