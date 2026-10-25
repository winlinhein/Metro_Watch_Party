# Fix 3 Critical Render Issues

## Fix 1 — Socket.io not running on Render
- [x] Create `docker-entrypoint.sh` (starts Node.js + Apache)
- [x] Create `apache-conf/socketio-proxy.conf` (Apache WebSocket proxy for /socket.io/)
- [x] Update `Dockerfile` (install Node.js, npm install, enable proxy modules, use entrypoint)
- [x] Update `.dockerignore` (allow server.ts, package.json, package-lock.json)

## Fix 2 — pusher_errors.log writes to read-only web root
- [x] Fix `pusher_helper.php` log path → sys_get_temp_dir()

## Fix 3 — RESEND_API_KEY not available on Render
- [x] Add hardcoded fallback in `mail_helper.php` (like Pusher/Stripe keys)
