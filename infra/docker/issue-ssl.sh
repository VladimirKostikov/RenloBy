#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
DOMAIN_PRIMARY="${DOMAIN_PRIMARY:-fixrent.by}"
DOMAIN_WWW="${DOMAIN_WWW:-www.fixrent.by}"
EMAIL="${CERTBOT_EMAIL:-admin@${DOMAIN_PRIMARY}}"
WEBROOT="${ROOT}/infra/docker/certbot/www"
LIVE_DIR="/etc/letsencrypt/live/${DOMAIN_PRIMARY}"
SSL_DIR="${ROOT}/infra/docker/ssl"

mkdir -p "${WEBROOT}/.well-known/acme-challenge" "${SSL_DIR}"

if ! command -v certbot >/dev/null 2>&1; then
  export DEBIAN_FRONTEND=noninteractive
  apt-get update -qq
  apt-get install -y -qq certbot
fi

certbot certonly \
  --webroot \
  --webroot-path "${WEBROOT}" \
  --agree-tos \
  --non-interactive \
  --email "${EMAIL}" \
  --preferred-challenges http \
  -d "${DOMAIN_PRIMARY}" \
  -d "${DOMAIN_WWW}" \
  --keep-until-expiring \
  --expand

install -m 644 "${LIVE_DIR}/fullchain.pem" "${SSL_DIR}/fullchain.pem"
install -m 600 "${LIVE_DIR}/privkey.pem" "${SSL_DIR}/privkey.pem"

cd "${ROOT}"
docker compose -f infra/docker/docker-compose.prod.yml --env-file .env up -d --force-recreate proxy

echo "[ssl] certificate installed for ${DOMAIN_PRIMARY}"
openssl x509 -in "${SSL_DIR}/fullchain.pem" -noout -subject -dates -ext subjectAltName
