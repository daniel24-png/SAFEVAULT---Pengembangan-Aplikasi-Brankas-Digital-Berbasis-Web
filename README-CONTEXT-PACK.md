# SAFEVAULT Context Pack

This package contains the adapted AI-agent instructions and project context for the existing SAFEVAULT PHP application.

## Contents

- `AGENTS.md`: permanent coding-agent rules and context routing.
- `INIT.md`: project bootstrap, current baseline, roadmap, and open decisions.
- `context/000` through `context/006`: task-specific architecture and implementation references.

## Installation

Copy `AGENTS.md`, `INIT.md`, and the `context/` directory into the root of the existing SAFEVAULT repository. Do not overwrite application source files, configuration, database files, or the existing `README.md`.

If the repository already has an `AGENTS.md`, `INIT.md`, or `context/` directory, compare and merge intentionally before replacing files. Keep a Git commit or backup before adding the context pack.

## Important

These documents distinguish the existing v0.1 implementation from future planned versions. The live code, actual database schema, and tests remain the source of truth for implementation details. Update the context files when a design decision is approved or a feature is completed.
