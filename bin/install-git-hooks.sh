#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

chmod +x .githooks/post-merge
git config core.hooksPath .githooks

echo "Hooks ativos em $(git config core.hooksPath)"
echo "A partir de agora, 'git pull' na main dispara o deploy automático (composer, build, migrate, reload)."
