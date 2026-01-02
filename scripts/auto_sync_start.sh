#!/usr/bin/env bash
# Start the auto-sync watcher on container start if AUTO_SYNC is not disabled
set -euo pipefail
REPO_ROOT=$(git rev-parse --show-toplevel 2>/dev/null || echo "/workspaces/lms")
cd "$REPO_ROOT"

if [ "${AUTO_SYNC:-1}" = "false" ] || [ "${AUTO_SYNC:-1}" = "0" ]; then
  echo "[auto-sync-start] AUTO_SYNC disabled by env var"
  exit 0
fi

# Avoid starting multiple instances
if [ -f .auto_sync_pid ]; then
  pid=$(cat .auto_sync_pid 2>/dev/null || true)
  if [ -n "$pid" ] && kill -0 "$pid" >/dev/null 2>&1; then
    echo "[auto-sync-start] auto-sync already running with PID $pid"
    exit 0
  fi
fi

# Ensure inotifywait exists (idempotent)
if ! command -v inotifywait >/dev/null 2>&1; then
  echo "[auto-sync-start] installing inotify-tools (may require sudo)"
  apt-get update -y && apt-get install -y inotify-tools || true
fi

# Start watcher detached
nohup bash scripts/auto_sync.sh 10 > /tmp/auto_sync.log 2>&1 & echo $! > .auto_sync_pid
echo "[auto-sync-start] started auto-sync (PID $(cat .auto_sync_pid))"
