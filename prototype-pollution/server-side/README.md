# Prototype Pollution → RCE practice labs

Two minimal Node.js + Express labs for local practice:

- `fork-execargv/` — polluted `execArgv` → `--eval` → JavaScript execution.
- `execsync-shell-input/` — polluted `shell` + `input` → Vim `:!` → external command execution.

Both applications intentionally contain a prototype-pollution vulnerability and are meant for isolated local use.

Each application has exactly one HTTP endpoint: `POST /run`.

## Requirements

- Node.js 18+
- npm
- Linux/macOS is recommended for the second lab because it uses Vim as the shell gadget.

## Important Node-version note

Current Node releases normalize child-process options in ways that can drop inherited properties. The labs therefore include a tiny, explicit **application-side gadget** that reads the inherited property and copies it into the real Node options object. This keeps the labs deterministic while preserving the PortSwigger lesson: prototype pollution controls a sensitive option that the application never explicitly configured.
