#!/usr/bin/env bash
set -euo pipefail

if [[ ${EUID:-$(id -u)} -ne 0 ]]; then
  echo "Run this installer with sudo: sudo bash whatsapp-gateway/install-local-service.sh"
  exit 1
fi

GATEWAY_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$GATEWAY_DIR/.." && pwd)"
CI_ENV="$ROOT_DIR/.env"
SERVICE_NAME="drmi-whatsapp-local"
PORT="${WHATSAPP_PORT:-3099}"

if [[ ! -f "$CI_ENV" ]]; then
  echo "Missing CI4 .env at: $CI_ENV"
  exit 1
fi

OWNER_USER="$(stat -c '%U' "$ROOT_DIR")"
if [[ "$OWNER_USER" == "root" && -n "${SUDO_USER:-}" && "${SUDO_USER:-}" != "root" ]]; then
  OWNER_USER="$SUDO_USER"
fi
APP_USER="${APP_USER:-$OWNER_USER}"
APP_GROUP="${APP_GROUP:-$(id -gn "$APP_USER")}"

find_bin() {
  local name="$1" candidate
  candidate="$(command -v "$name" 2>/dev/null || true)"
  if [[ -n "$candidate" && -x "$candidate" ]]; then echo "$candidate"; return 0; fi
  for candidate in \
      /usr/bin/$name \
      /usr/local/bin/$name \
      /www/server/nodejs/*/bin/$name \
      /module/node/*/bin/$name; do
    if [[ -x "$candidate" ]]; then echo "$candidate"; return 0; fi
  done
  return 1
}

NODE_BIN="${NODE_BIN:-$(find_bin node || true)}"
NPM_BIN="${NPM_BIN:-$(find_bin npm || true)}"

if [[ -z "$NODE_BIN" || -z "$NPM_BIN" ]]; then
  echo "Node.js/npm not found. Install Node.js first or run with NODE_BIN=/path/to/node NPM_BIN=/path/to/npm."
  exit 1
fi

if ! command -v openssl >/dev/null 2>&1; then
  echo "openssl is required to generate the local bridge key."
  exit 1
fi

BRIDGE_KEY="${BRIDGE_KEY:-$(openssl rand -hex 32)}"

# Keep CI4 and Node on the exact same randomly generated secret.
if grep -qE '^[[:space:]]*whatsapp\.bridgeKey[[:space:]]*=' "$CI_ENV"; then
  sed -i -E "s#^[[:space:]]*whatsapp\.bridgeKey[[:space:]]*=.*#whatsapp.bridgeKey = '$BRIDGE_KEY'#" "$CI_ENV"
else
  printf "\nwhatsapp.bridgeKey = '%s'\n" "$BRIDGE_KEY" >> "$CI_ENV"
fi

if grep -qE '^[[:space:]]*whatsapp\.bridgePort[[:space:]]*=' "$CI_ENV"; then
  sed -i -E "s#^[[:space:]]*whatsapp\.bridgePort[[:space:]]*=.*#whatsapp.bridgePort = $PORT#" "$CI_ENV"
else
  printf "whatsapp.bridgePort = %s\n" "$PORT" >> "$CI_ENV"
fi

# An old public/URL setting is intentionally disabled; the PHP service ignores it anyway.
if grep -qE '^[[:space:]]*whatsapp\.bridgeUrl[[:space:]]*=' "$CI_ENV"; then
  sed -i -E 's|^[[:space:]]*(whatsapp\.bridgeUrl[[:space:]]*=.*)|# deprecated: \1|' "$CI_ENV"
fi

cat > "$GATEWAY_DIR/.env" <<EOF
PORT=$PORT
BRIDGE_KEY=$BRIDGE_KEY
SESSION_ID=mobile-shop-main
SESSION_PATH=$GATEWAY_DIR/sessions
DEFAULT_COUNTRY_CODE=91
EOF

mkdir -p "$GATEWAY_DIR/sessions"
chown -R "$APP_USER:$APP_GROUP" "$GATEWAY_DIR"
chmod 750 "$GATEWAY_DIR/sessions"
chmod 640 "$GATEWAY_DIR/.env"

# Install Node dependencies as the same account that will own the WhatsApp session.
echo "Installing WhatsApp gateway dependencies with $NPM_BIN ..."
runuser -u "$APP_USER" -- env PATH="$(dirname "$NODE_BIN"):$(dirname "$NPM_BIN"):/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin" "$NPM_BIN" install --omit=dev --no-audit --no-fund --prefix "$GATEWAY_DIR"

cat > "/etc/systemd/system/${SERVICE_NAME}.service" <<EOF
[Unit]
Description=DRMI WhatsApp Web Local Bridge
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
User=$APP_USER
Group=$APP_GROUP
WorkingDirectory=$GATEWAY_DIR
Environment=NODE_ENV=production
ExecStart=$NODE_BIN $GATEWAY_DIR/server.js
Restart=always
RestartSec=5
TimeoutStopSec=30
KillSignal=SIGINT

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable --now "$SERVICE_NAME"

sleep 3

echo
echo "Service status:"
systemctl status "$SERVICE_NAME" --no-pager -l || true

echo
echo "Testing loopback endpoint from this server:"
if command -v curl >/dev/null 2>&1; then
  curl -fsS -H "X-Bridge-Key: $BRIDGE_KEY" "http://127.0.0.1:$PORT/status" || true
  echo
else
  echo "curl not installed; skip HTTP check."
fi

echo
echo "Next checks:"
echo "  cd $ROOT_DIR"
echo "  php spark whatsapp:check"
echo "  sudo journalctl -u $SERVICE_NAME -n 100 --no-pager"
echo
echo "Then open the WhatsApp page in DRMI and scan the QR code."
