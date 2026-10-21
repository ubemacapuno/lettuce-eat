#!/bin/bash
# One-time setup: install Docker, build/run lettuce-eat, expose it via tailscale serve
# on a distinct port so it doesn't clobber the existing melnor-pi-bridge route on ube-pi.
#
# Run with: sudo bash setup-docker-tailscale.sh
set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
  echo "Run this with sudo: sudo bash $0" >&2
  exit 1
fi

REAL_USER="${SUDO_USER:-damoclescj}"
APP_DIR="/home/${REAL_USER}/apps/lettuce-eat"
TS_PORT=8443

echo "==> Installing Docker (Debian's native packages) if needed"
if ! command -v docker >/dev/null 2>&1; then
  apt-get update
  apt-get install -y docker.io docker-compose
else
  echo "docker already installed: $(docker --version)"
fi

systemctl enable --now docker

echo "==> Adding ${REAL_USER} to the docker group (takes effect on next login)"
usermod -aG docker "${REAL_USER}"

# Debian's docker-compose package may only provide the standalone
# `docker-compose` binary rather than registering the `docker compose`
# CLI-plugin subcommand, so detect which one actually works.
if docker compose version >/dev/null 2>&1; then
  COMPOSE=(docker compose)
else
  COMPOSE=(docker-compose)
fi
echo "==> Using compose command: ${COMPOSE[*]}"

echo "==> Building the lettuce-eat image"
cd "${APP_DIR}"
"${COMPOSE[@]}" build

echo "==> Starting the container"
"${COMPOSE[@]}" up -d

echo "==> Waiting for the app to become healthy"
for i in $(seq 1 30); do
  if curl -fsS http://127.0.0.1:8080/up >/dev/null 2>&1; then
    echo "App is up."
    break
  fi
  sleep 2
done

echo "==> Exposing it on the tailnet at https://ube-pi.tail908b50.ts.net:${TS_PORT}/"
tailscale serve --bg --https="${TS_PORT}" http://127.0.0.1:8080

echo "==> Current tailscale serve config:"
tailscale serve status

echo
echo "Done. Visit: https://ube-pi.tail908b50.ts.net:${TS_PORT}/"
echo "Note: your shell group membership won't refresh until you log out/in again,"
echo "but that's only needed if you want to run 'docker' without sudo going forward."
