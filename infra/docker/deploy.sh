#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

git config --global --add safe.directory "$ROOT" 2>/dev/null || true

COMPOSE=(docker compose -f infra/docker/docker-compose.prod.yml --env-file "$ROOT/.env")

echo "[deploy] pulling code"
if [ -d .git ]; then
  git fetch --all --prune
  git reset --hard "${DEPLOY_REF:-origin/main}"
fi

echo "[deploy] building images"
"${COMPOSE[@]}" build backend frontend

echo "[deploy] restarting stack"
"${COMPOSE[@]}" up -d --remove-orphans

echo "[deploy] waiting for backend"
for i in $(seq 1 60); do
  status="$(docker inspect --format='{{.State.Health.Status}}' renlo-backend-1 2>/dev/null || echo missing)"
  if [ "$status" = "healthy" ]; then
    break
  fi
  sleep 5
done

echo "[deploy] health check"
curl -fkSs https://127.0.0.1/api/health >/dev/null

echo "[deploy] done"
"${COMPOSE[@]}" ps
