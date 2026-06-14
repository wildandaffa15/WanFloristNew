import pathlib
import re

root = pathlib.Path(__file__).resolve().parent.parent

# Pattern to match decorative separators: ═, ─, =, -, *, _, etc. (10+ chars)
decorative_pattern = re.compile(r'[\-=═_*─]{10,}')

file_extensions = {'.php', '.css', '.js'}
summary = {}

for path in [p for p in root.rglob('*.*') if p.suffix.lower() in file_extensions]:
    text = path.read_text(encoding='utf-8')
    lines = text.splitlines()
    new_lines = []
    removed = 0
    i = 0

    while i < len(lines):
        line = lines[i]
        stripped = line.lstrip()

        # HTML comment block
        if stripped.startswith('<!--'):
            j = i
            block = [lines[j]]
            while j < len(lines) and '-->' not in lines[j]:
                j += 1
                if j < len(lines):
                    block.append(lines[j])
            block_text = '\n'.join(block)
            if any(decorative_pattern.search(l) for l in block) and len(block) > 1:
                removed += len(block)
                i = j + 1
                continue
            new_lines.extend(block)
            i = j + 1
            continue

        # C-style block comment
        if stripped.startswith('/*'):
            j = i
            block = [lines[j]]
            while j < len(lines) and '*/' not in lines[j]:
                j += 1
                if j < len(lines):
                    block.append(lines[j])
            if any(decorative_pattern.search(l) for l in block):
                removed += len(block)
                i = j + 1
                continue
            new_lines.extend(block)
            i = j + 1
            continue

        # Single-line comment decoration
        if stripped.startswith('//') or stripped.startswith('#') or stripped.startswith('<!--'):
            if decorative_pattern.search(line):
                removed += 1
                i += 1
                continue

        new_lines.append(line)
        i += 1

    if removed:
        summary[path] = removed
        path.write_text('\n'.join(new_lines) + ('\n' if text.endswith('\n') else ''), encoding='utf-8')

for path, removed in summary.items():
    print(f'{path.relative_to(root)}: removed {removed} decorative comment lines')
print(f'Total files modified: {len(summary)}')
