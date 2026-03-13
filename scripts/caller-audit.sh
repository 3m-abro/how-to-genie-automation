#!/usr/bin/env bash
# Phase 8 — list every active-dir file that references a workflow's root id (caller discovery).
# Usage: scripts/caller-audit.sh <path-to-workflow.json>
# Output: one caller file path per line (stdout); exit 1 if workflow has no root id.

set -e
if [ $# -lt 1 ]; then
  echo "Usage: $0 <path-to-workflow.json>" >&2
  exit 1
fi
WF_PATH="$1"
REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$REPO_ROOT"

if [ ! -f "$WF_PATH" ]; then
  echo "caller-audit: file not found: $WF_PATH" >&2
  exit 1
fi

id="$(node -e "
  try {
    const w = JSON.parse(require('fs').readFileSync(process.argv[1], 'utf8'));
    if (!w.id) {
      process.stderr.write('caller-audit: no root id in ' + process.argv[1] + '\n');
      process.exit(1);
    }
    console.log(w.id);
  } catch (e) {
    process.stderr.write('caller-audit: parse error: ' + process.argv[1] + '\n');
    process.exit(1);
  }
" "$WF_PATH")" || exit 1

ACTIVE_DIRS="core content growth social affiliate monitoring email"
grep -r --include='*.json' -l "$id" $ACTIVE_DIRS 2>/dev/null || true
