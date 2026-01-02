Auto Sync (aggressive)

What it does
- Watches the repository for file changes and automatically stages, commits and pushes them to `origin/<current-branch>`.

Defaults
- Debounce: 10s
- Excluded paths (also in `.gitignore`): `.git`, `node_modules`, `vendor`, `storage`, `.env`, `Lernen/lernen-main-file/upgrade/upgrade.zip`.

Start
- Run the VS Code task: **Terminal → Run Task → Start Auto Sync**
- Or run `bash scripts/auto_sync.sh 10` in a terminal.

Safety notes
- This will create frequent commits. Keep secrets out of tracked files; ensure `.env`/other sensitive files are ignored.
- On conflicts, the script will attempt `git pull --rebase`; if that fails it pushes to `auto-sync/<timestamp>`.

Stop
- Stop the task or kill the process (Ctrl+C in the terminal running it).

Automatic start in Codespaces / DevContainers
- The devcontainer is configured to auto-start the watcher on container start using `postStartCommand`.
- To disable automatic startup, set the environment variable `AUTO_SYNC=false` in your Codespace or container.

Contact
- If you want different excludes, debounce values, or conflict behavior, edit `scripts/auto_sync.sh`.
