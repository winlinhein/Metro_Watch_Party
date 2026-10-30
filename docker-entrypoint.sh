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

# Trap SIGTERM/SIGINT so both processes shut down cleanly
trap 'kill "$NODE_PID" 2>/dev/null || true; wait "$NODE_PID" 2>/dev/null || true; exit 0' SIGTERM SIGINT

# Do not let Apache accept traffic until its Socket.IO proxy target is ready.
# Otherwise Render can mark the service live while Node is still starting, and
# room clients receive transient 503s from /socket.io/ during that window.
NODE_READY=0
for attempt in $(seq 1 40); do
    if ! kill -0 "$NODE_PID" 2>/dev/null; then
        echo "[entrypoint] Node.js signaling server exited before becoming ready."
        wait "$NODE_PID" || true
        exit 1
    fi
    if (echo > /dev/tcp/127.0.0.1/3000) >/dev/null 2>&1; then
        NODE_READY=1
        break
    fi
    sleep 0.5
done

if [ "$NODE_READY" -ne 1 ]; then
    echo "[entrypoint] Node.js signaling server did not listen on port 3000 within 20 seconds."
    kill "$NODE_PID" 2>/dev/null || true
    wait "$NODE_PID" 2>/dev/null || true
    exit 1
fi

echo "[entrypoint] Node.js signaling server is ready. Starting Apache..."

# Run Apache in the foreground (keeps container alive)
apache2-foreground
