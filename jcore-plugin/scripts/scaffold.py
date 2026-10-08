#!/usr/bin/env python3
"""Render the jcore plugin templates into a plugin directory.

New plugin:   scaffold.py ../jcore-example --name "JCORE Example" --description "…" [--admin] [--blocks]
Migration:    scaffold.py . --name "JCORE Portti" --description "…" --blocks --diff

Existing files are never overwritten. With --diff nothing is written; each
template is compared with what the repo has, which is the quickest way to see
how far an older plugin has drifted from the current conventions.
"""

import argparse
import difflib
import re
import sys
import zlib
from pathlib import Path

TEMPLATES = Path(__file__).resolve().parent.parent / "templates"

# Files that only exist for one variant. Everything else is always rendered.
ONLY_WITH = {
    "admin": [
        "includes/admin/class-menu.php",
        "includes/rest/class-controller.php",
        "includes/rest/class-settings-controller.php",
        "views/admin/page.php",
        "src/admin/index.js",
        "src/admin/App.js",
        "src/admin/style.scss",
        "webpack.config.js",
    ],
}

SLUG_RE = re.compile(r"^jcore-[a-z0-9]+(?:-[a-z0-9]+)*$")
BLOCK_SECTION_RE = re.compile(r"^[ \t]*\{\{([#^])(\w+)\}\}\n(.*?)^[ \t]*\{\{/\2\}\}\n", re.M | re.S)
INLINE_SECTION_RE = re.compile(r"\{\{([#^])(\w+)\}\}(.*?)\{\{/\2\}\}", re.S)
VAR_RE = re.compile(r"\{\{(\w+)\}\}")


def names(slug: str) -> dict:
    words = slug.split("-")[1:]
    return {
        "slug": slug,
        "ns": "".join(w.capitalize() for w in words),
        "CONST": "JCORE_" + "_".join(w.upper() for w in words),
        "prefix": "jcore_" + "_".join(words),
    }


def render(text: str, values: dict, flags: dict) -> str:
    def section(match: re.Match) -> str:
        kind, flag, body = match.groups()
        return body if flags.get(flag, False) == (kind == "#") else ""

    text = BLOCK_SECTION_RE.sub(section, text)
    text = INLINE_SECTION_RE.sub(section, text)

    def var(match: re.Match) -> str:
        key = match.group(1)
        if key not in values:
            raise KeyError(f"template uses unknown placeholder {{{{{key}}}}}")
        return values[key]

    return VAR_RE.sub(var, text)


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("target", help="Plugin directory; its name is the slug (jcore-<word>).")
    parser.add_argument("--name", required=True, help='Display name, e.g. "JCORE Example".')
    parser.add_argument("--description", required=True, help="One-sentence description.")
    parser.add_argument("--admin", action="store_true", help="Settings screen with a React app and REST routes.")
    parser.add_argument("--blocks", action="store_true", help="Register blocks from build/blocks-manifest.php.")
    parser.add_argument("--tested", default="7.1", help='"Tested up to" WordPress version (default 7.1).')
    parser.add_argument("--port", help="Playground port (default: derived from the slug).")
    parser.add_argument("--diff", action="store_true", help="Show differences against existing files; write nothing.")
    args = parser.parse_args()

    target = Path(args.target).resolve()
    slug = target.name
    if not SLUG_RE.match(slug):
        print(f"error: directory name {slug!r} is not a jcore slug (jcore-<word>)", file=sys.stderr)
        return 2

    values = names(slug)
    values.update(
        name=args.name,
        description=args.description,
        tested=args.tested,
        port=args.port or str(8800 + zlib.crc32(slug.encode()) % 100),
        landing=f"/wp-admin/options-general.php?page={slug}" if args.admin else "/wp-admin/plugins.php",
    )
    flags = {"admin": args.admin, "blocks": args.blocks}
    skipped = {f for flag, files in ONLY_WITH.items() if not flags[flag] for f in files}

    created, same, differs = [], [], []
    for src in sorted(p for p in TEMPLATES.rglob("*") if p.is_file()):
        rel = src.relative_to(TEMPLATES).as_posix()
        if rel in skipped:
            continue
        out_rel = rel.replace("__slug__", slug)
        content = render(src.read_text(), values, flags)
        dest = target / out_rel

        if dest.exists():
            current = dest.read_text()
            if current == content:
                same.append(out_rel)
                continue
            differs.append(out_rel)
            if args.diff:
                sys.stdout.writelines(
                    difflib.unified_diff(
                        current.splitlines(True), content.splitlines(True), f"a/{out_rel}", f"b/{out_rel} (template)"
                    )
                )
            continue

        created.append(out_rel)
        if not args.diff:
            dest.parent.mkdir(parents=True, exist_ok=True)
            dest.write_text(content)

    if not args.diff:
        (target / "languages").mkdir(parents=True, exist_ok=True)

    verb = "would create" if args.diff else "created"
    print(f"\n{slug}: Jcore\\{values['ns']}, {values['CONST']}_*, {values['prefix']}_*, port {values['port']}")
    for label, files in ((verb, created), ("differs (left alone)", differs), ("identical", same)):
        if files:
            print(f"\n{label}:")
            print("\n".join(f"  {f}" for f in files))
    return 0


if __name__ == "__main__":
    sys.exit(main())
