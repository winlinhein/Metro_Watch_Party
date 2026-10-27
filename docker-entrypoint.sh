#!/bin/bash
set -e

# ---------------------------------------------------------------------------
# docker-entrypoint.sh
#
# Starts the Node.js Socket.io signaling server (server.ts) in the background
# and then runs Apache in the foreground.
#
# On Render:  Apache proxies /socket.io/ to localhost:3000 (this script).
# Locally:    server.ts also runs on :3000; the PHP dev server runs on another
#             port so the browser connects directly to localhost:3000.
# ---------------------------------------------------------------------------

echo "[entrypoint] Starting Node.js signaling server on port 3000..."
cd /var/www/html

# Use npx tsx to run TypeScript directly (tsx is in node_modules/.bin)
npx tsx server.ts &
NODE_PID=$!

echo "[entrypoint] Node.js PID: $NODE_PID"
echo "[entrypoint] Starting Apache..."

# Trap SIGTERM/SIGINT so both processes shut down cleanly
trap "kill $NODE_PID 2>/dev/null; exit 0" SIGTERM SIGINT

# Run Apache in the foreground (keeps container alive)
apache2-foreground
