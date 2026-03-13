#!/usr/bin/env bash
# Phase 8 — assert no active workflow references an archived workflow id (ARCH-02).
# For each workflow in archive/, collects root "id"; greps that id in active dirs only.
# Exits 1 if any active JSON references an archived workflow id; 0 otherwise.

set -e
REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$REPO_ROOT"

ACTIVE_DIRS="core content growth social affiliate monitoring email"
FAIL=0
while IFS= read -r -d '' json_path; do
  [ -z "$json_path" ] && continue
  id="$(node -e "
    try {
      const w = JSON.parse(require('fs').readFileSync(process.argv[1], 'utf8'));
      if (!w.id) {
        process.stderr.write('verify-archive-refs: no root id: ' + process.argv[1] + '\n');
        process.exit(0);
      }
      console.log(w.id);
    } catch (e) {
      process.stderr.write('verify-archive-refs: skip (parse error): ' + process.argv[1] + '\n');
      process.exit(0);
    }
  " "$json_path" 2>/dev/null)" || id=""
  [ -z "$id" ] && continue
  matches="$(grep -r --include='*.json' -l "$id" $ACTIVE_DIRS 2>/dev/null || true)"
  if [ -n "$matches" ]; then
    echo "$matches" | while IFS= read -r m; do
      echo "Active workflow references archived id ($id from $json_path): $m" >&2
    done
    FAIL=1
  fi
done < <(find archive -maxdepth 1 -name '*.json' -print0 2>/dev/null)

[ "$FAIL" -eq 1 ] && exit 1
exit 0
