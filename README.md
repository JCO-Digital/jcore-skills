# JCORE Agent Skills

Agent skills and tooling for J&Co Digital's WordPress development ecosystem.

These skills teach AI coding assistants (Zed, Claude Code, Cursor, Copilot, Codex, etc.) the architecture, tooling, coding standards, and release workflows used across J&Co Digital's repositories.

## Available Skills

| Skill                                | Description                                                                                                                                                                            |
| ------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **[`jcore-plugin`](./jcore-plugin)** | Conventions, architecture, scaffolding, migration, and release pipeline for J&Co Digital's `jcore-*` WordPress plugins (`jcore-turva`, `jcore-maailma`, `jcore-portti`, and siblings). |

---

## Installation & Setup

Skills can be installed globally (available across all projects on your machine) or scoped to a specific project.

### Global Installation

To make skills available everywhere, symlink or copy them into your agent's global skills directory:

#### Claude Code (`~/.claude/skills`)

```bash
mkdir -p ~/.claude/skills
ln -s "$(pwd)/jcore-plugin" ~/.claude/skills/jcore-plugin
```

#### Zed (`~/.agents/skills`)

```bash
mkdir -p ~/.agents/skills
ln -s "$(pwd)/jcore-plugin" ~/.agents/skills/jcore-plugin
```

#### Cursor (`~/.cursor/skills`)

```bash
mkdir -p ~/.cursor/skills
ln -s "$(pwd)/jcore-plugin" ~/.cursor/skills/jcore-plugin
```

### Project-Level Installation

To provide the skill within a specific repository, link or copy it into the project's agent directory:

- **Zed**: `.agents/skills/jcore-plugin`
- **Claude Code**: `.claude/skills/jcore-plugin`
- **Cursor**: `.cursor/skills/jcore-plugin`
- **GitHub Copilot / VS Code**: `.github/skills/jcore-plugin`

---

## Skill: `jcore-plugin`

`jcore-plugin` encapsulates conventions for private JCORE plugins distributed via `jcore-update` and `update.jcore.fi`.

### Key Features & Workflow

- **Scaffolding (`scripts/scaffold.py`)**: Generates fresh plugins matching current J&Co Digital standards with proper naming, namespaces, Composer, pnpm, and CI configuration.
- **Migration**: Tools and guides (`references/migration.md`) to bring legacy plugins up to modern standards (pre-2026 patterns, `.wp-env.json`, manual version syncs).
- **Architecture**: Enforces thin bootstraps, strict namespacing, isolated text domains, React-based admin apps, and safe schema upgrade patterns (`references/php-conventions.md`).
- **Tooling**: Standardized configurations for `@wordpress/scripts`, pnpm 11, PHPCS (WordPress-Docs + WordPress-Extra + PHPCompatibilityWP 8.2+), and WordPress Playground via `.wp/blueprint.json` (`references/tooling.md`).
- **Automated Releases**: Uses `foonver` for semantic versioning derived from Conventional Commits, with publishing automated through GitHub Actions and `jcore-update` (`references/release.md`).

### Scaffolding a Plugin

Run the included scaffolding script to generate a new plugin:

```bash
python3 jcore-plugin/scripts/scaffold.py ../jcore-example \
  --name "JCORE Example" \
  --description "One sentence description of the plugin." \
  --admin \
  --blocks
```

Flags:

- `--admin`: Adds React admin settings page, REST controller, and build entries.
- `--blocks`: Enables block registration manifest.
- `--diff`: Compares generated templates against an existing plugin directory without writing changes (useful for inspecting drift during migrations).

> **Reference Implementation:** `jcore-turva` is the canonical reference implementation. When conventions evolve, Turva reflects the latest production setup.

---

## Repository Layout

```
jcore/
├── README.md
└── jcore-plugin/
    ├── SKILL.md              # Core skill instructions and prompt trigger criteria
    ├── evals/                # Test prompts and evaluation cases
    ├── references/           # Detailed topic guides
    │   ├── jcore-update.md   # Update client integration & scoping rules
    │   ├── migration.md      # Upgrading older plugins
    │   ├── php-conventions.md# Bootstrap, class layout, hooks, REST, DB
    │   ├── release.md        # CI/CD, foonver, and publishing workflows
    │   └── tooling.md        # Config files, linters, dependencies
    ├── scripts/
    │   └── scaffold.py       # Plugin generator & diff utility
    └── templates/            # Base templates for plugin files and workflows
```
