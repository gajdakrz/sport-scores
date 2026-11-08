#!/usr/bin/env bash
# PreToolUse guard for Edit|Write: committed migrations are immutable.
# A migration that already exists in HEAD may have been run on other databases,
# so it must not be changed – create a new migration instead.
# Uncommitted migrations (new, staged or not) may be edited.
set -euo pipefail

file_path=$(jq -r '.tool_input.file_path // empty')

case "$file_path" in
    */api/migrations/*) ;;
    *) exit 0 ;;
esac

repo_root=$(git -C "$(dirname "$file_path")" rev-parse --show-toplevel 2>/dev/null) || exit 0
relative_path=${file_path#"$repo_root"/}

if git -C "$repo_root" cat-file -e "HEAD:$relative_path" 2>/dev/null; then
    cat >&2 <<EOF
Blocked: $relative_path is already committed. Don't edit existing migrations – create a new one
(php bin/console make:migration). See AGENTS.md: "Don't edit existing files in migrations/".
EOF
    exit 2
fi

# Uncommitted migration: allowed. If it was already executed locally, rebuild the database after editing.
exit 0