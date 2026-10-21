#!/bin/bash
# Pull the latest main and rebuild/restart the running container.
# The SQLite DB and storage live in ./data (bind-mounted, outside the image),
# so this never touches them.
set -euo pipefail

cd "$(dirname "$0")"

if docker compose version >/dev/null 2>&1; then
  COMPOSE=(docker compose)
else
  COMPOSE=(docker-compose)
fi

echo "==> Pulling latest main"
git pull origin main

echo "==> Rebuilding image"
"${COMPOSE[@]}" build --pull

echo "==> Recreating container"
"${COMPOSE[@]}" up -d

echo "==> Waiting for the app to become healthy"
for i in $(seq 1 30); do
  if curl -fsS http://127.0.0.1:8080/up >/dev/null 2>&1; then
    echo "App is up."
    exit 0
  fi
  sleep 2
done

echo "Warning: app did not report healthy after rebuild. Check: docker compose logs -f" >&2
