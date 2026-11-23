#!/usr/bin/env bash
set -euo pipefail
cd /home/pedrocaetano/memorias_v2

if ! grep -qx 'config/psysh/' .gitignore; then
  printf '%s\n' 'config/psysh/' >> .gitignore
fi

git rm -r --cached config/psysh 2>/dev/null || true
git add .gitignore
git add -A

printf '%s\n' 'Initial commit of Memorias admin application.' > /tmp/commitmsg.txt
git commit -F /tmp/commitmsg.txt
git log -1 --oneline
git status -sb | head -20
