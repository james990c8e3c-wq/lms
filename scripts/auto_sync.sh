#!/usr/bin/env bash
# Auto-sync script: watches for changes and auto commits + pushes
# Usage: scripts/auto_sync.sh [debounce_seconds]

set -euo pipefail
DEBOUNCE=${1:-10}
REPO_ROOT=$(git rev-parse --show-toplevel)
cd "$REPO_ROOT"
BRANCH=$(git rev-parse --abbrev-ref HEAD)
EXCLUDE_REGEX='(^|/)(\.git|node_modules|vendor|storage|\.env$|\.env\.|Lernen/lernen-main-file/upgrade/upgrade.zip)$'
LOG_PREFIX="[auto-sync]"

trap "echo \"$LOG_PREFIX exiting\"; exit 0" SIGINT SIGTERM

# Debounced loop: wait for a change, sleep for DEBOUNCE, then commit+push
while true; do
  inotifywait -r -e modify,create,delete,move --exclude "$EXCLUDE_REGEX" . >/dev/null 2>&1 || true
  # wait a short time for additional changes to accumulate
  sleep "$DEBOUNCE"

  # Only proceed if there are unstaged/staged changes not empty
  if git status --porcelain | grep -q '.'; then
    echo "$LOG_PREFIX Detected changes, preparing commit..."

    # Stage all (respects .gitignore)
    git add -A

    # If nothing to commit (e.g., only ignored files), continue
    if git diff --cached --quiet; then
      echo "$LOG_PREFIX Nothing to commit after staging (likely ignored files)."
      continue
    fi

    # Create an informative commit message
    MSG="Auto-sync: $(date -u +'%Y-%m-%dT%H:%M:%SZ')"
    git commit -m "$MSG" || true

    # Try to pull --rebase to avoid simple divergent history issues
    if git fetch origin "$BRANCH" >/dev/null 2>&1; then
      if ! git pull --rebase origin "$BRANCH"; then
        NEWBRANCH="auto-sync/$(date +%s)"
        echo "$LOG_PREFIX Rebase failed; pushing to new branch $NEWBRANCH"
        git push origin HEAD:$NEWBRANCH || echo "$LOG_PREFIX push failed for $NEWBRANCH"
        continue
      fi
    fi

    # Push current branch
    if ! git push origin "$BRANCH"; then
      NEWBRANCH="auto-sync/$(date +%s)"
      echo "$LOG_PREFIX Push failed; pushing to new branch $NEWBRANCH"
      git push origin HEAD:$NEWBRANCH || echo "$LOG_PREFIX final push failed"
    else
      echo "$LOG_PREFIX Pushed to origin/$BRANCH"
    fi
  fi
done
