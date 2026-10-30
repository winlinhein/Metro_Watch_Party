# Video Call Fix Plan — Watch Party (branch `vd-calling-adjust`)

Prepared from a full read of the room client, signaling server, and backend endpoints.
**Audience:** the implementing model (DeepSeek). Follow phases in order, commit per phase.
**Scope:** video/voice not appearing between users; connecting state must gate room entry; back arrow must fully leave; npm startup verified.

---

## Part 0 — How the room actually works (read this first)

The call is a **WebRTC full mesh** with **two separate transport channels**:

| Channel | Technology | Carries |
|---|---|---|
| Signaling | **Pusher** channel `private-watch-party-{roomId}` | offers, answers, ICE candidates, peer-join/leave/park/resume, chat, playback, room-ended, toggle-mic/video, movie-changed |
| Lightweight events | **Socket.IO** (`/socket.io/`, Node `server.ts` on port 3000, Apache-proxied) | `peer-speaking` only (+ lobby invites) |

This split is **intentional** — see comment at `user/watch_party.js:2111-2114`. **Do not merge them or route SDP over Socket.IO.** The Socket.IO handshake error in F12 is expected noise when the token is missing/bad, but it does NOT block media: media signaling goes over Pusher.

Key files and responsibilities:

- `user/watch_party.js` (2943 lines) — Alpine `watchParty()` client. All fix work in Phase A/B/C/D happens here.
- `user/watch_party.php` (901 lines) — room page, Alpine bindings, back-arrow button (line 288), `isLeaving` overlay (383-389). Phase C adds the connecting overlay here.
- `user_backend/room_signal.php` — Pusher event relay; validates membership via `last_seen > NOW() - 90s`.
- `user_backend/join_room.php` — presence roster (polled), broadcasts `peer-join` via Pusher.
- `user_backend/leave_room.php` — proper leave (`park` param parks without closing; `unload` backdates presence).
- `user_backend/pusher_auth.php` — private channel auth; requires active (90s) membership.
- `server.ts` (843 lines) — Socket.IO signaling server, HMAC token auth (`verifySocketToken`, lines 30-58).
- `js/live_room_client.js` (714 lines) — the **dashboard** audio client. It already has two patterns `watch_party.js` is missing: bounded `waitForStable(pc)` with 2.5s timeout (lines 285-301) and `pc.onconnectionstatechange` cleanup (276-281). **Port both in Phase B.**
- `socket_auth_helper.php` — `nexusCreateSocketToken`; returns `''` when `PUSHER_SECRET` empty → handshake "Authentication required".
- `js/barba_setup.js` — `nexusHardNavigateUrl()` hard-navigates room/dashboard pages; do not break this.

Peer identity: each participant row has a unique `peer_id` (UUID). A "returning" visitor gets a **new** peer_id (`nexus_room_returning === '1'` → `restoreOrCreatePeerId`, watch_party.js:72-83) — this is why the back-arrow park creates stale rows/PCs (Phase D fixes it).

---

## Part 1 — Verified environment facts (already tested, do not re-verify)

1. **npm starts smoothly.** `npm run dev` / `npm start` → `tsx server.ts`; server listens on `0.0.0.0:3000`, answers HTTP 200 on `GET /` (probe with `curl -s -o /dev/null -w "%{http_code}" http://localhost:3000/` after ~5s warm-up). `tsx` is a production dependency — no install problem. Docker entrypoint (`docker-entrypoint.sh`) starts `npx tsx server.ts` then Apache, with `/socket.io/` proxied (WS + HTTP) via `socketio-proxy.conf`.
2. **Server-side Socket.IO auth works.** Raw polling handshake test: GET `open` packet → POST `40{"token":"<valid>"}` → `40{"sid":"..."}`; with garbage token → `44{"message":"Authentication required"}`. So a handshake error in F12 means the **client token is empty/mismatched or the proxy to :3000 is unreachable** — see Phase E.
3. **Working tree clean** on branch `vd-calling-adjust`; Barba hard-navigates watch_party.php/dashboard.php (no AJAX interference).

---

## Part 2 — Root-cause hypotheses (ranked)

**H1 (most likely — matches symptoms exactly): Pusher room subscription never succeeds, so no offer is ever sent.**
`bindPusherRoom()` (watch_party.js:2375-2412) sets `_roomSubscriptionReady=false`, then `true` only on `pusher:subscription_succeeded`. On `pusher:subscription_error` it only logs + toasts — **no retry**. `callPeer()` (1861-1914) queues into `_pendingPeerCalls` forever when the flag is false. Result: roster (DB polling) shows both users, but **zero SDP/ICE traffic** — blank tiles, no voice, and the only console error is the unrelated Socket.IO handshake. Subscription can fail for: 403 from `pusher_auth.php` (membership row older than 90s at auth time), expired Pusher key/secret, CSP/network block.

**H2: ICE fails to connect.** Candidate relay depends on `sendPusherSignal` → `room_signal.php` (POST) and TURN availability from `js/ice_servers.js`. If TURN is missing/off, symmetric NAT pairs never connect. `pc.onicecandidate` exists (1750-1810) but there is **no `oniceconnectionstatechange` handler and no restartIce retry** — a stuck `checking` state is invisible and permanent.

**H3: `publishLocalTracks()` deadlock.** Lines 1812-1859 wait for `signalingState === 'stable'` via an **unbounded** `signalingstatechange` listener. With two-phase media (audio first, camera in background — `startLocalMedia` 1631-1713), a concurrent offer can leave the PC non-stable and the wait hangs forever, stalling subsequent renegotiation.

**H4: Glare / stale-state edge cases.** `handleOffer` (1916-1962) does glare rollback but no perfect-negotiation `makingOffer` guard; `handleIceCandidate` (1980-1996) has a `pendingCandidates` buffer; `waitForIce(pc)` (1735-1748) exists but is **dead code** — offers are sent without waiting for ICE gathering (mostly fine with trickle, but combined with H3/H2 it contributes).

**H5 (UX bugs, confirmed by code):**
- `markConnected()` (296-300) only sets `isConnecting=false`; it is called 3× in `init()` (213, 230, 233), again in `announcePresence()` (2441/2446), and `applyMovie` also clears it (line 1232). There is **no connecting overlay in watch_party.php** — the flag is never bound to UI. Users enter a "room" that isn't connected yet. **Fix in Phase C.**
- Back arrow `goToDashboard()` (451-476) **parks**: persists active room, sends `peer-park`, no teardown, no `peer-leave`, `window.location.href='dashboard.php'`. Rejoining creates a new peerId → stale participant rows and orphan PCs. **Fix in Phase D.**

---

## Part 3 — Implementation phases (in order; commit after each)

### Phase A — Diagnostics behind `?debug=1` (do first; 30-60 min)

Goal: make the next failure self-reporting. All logging gated on `new URLSearchParams(location.search).has('debug')` so production stays clean.

In `user/watch_party.js`:
1. Add a `this._log(tag, ...args)` helper that prints only when debug is on (and always `console.error`s real errors).
2. Log in `bindPusherRoom()`: channel name, `pusher:subscription_succeeded`, `pusher:subscription_error` (with error object), and `pusher:connection` states (`connected/connecting/disconnected/failed` — bind in `announcePresence`/`init`).
3. Log in `signal()`/`sendPusherSignal()`: event type, target peerId, HTTP status of the POST, response body on failure.
4. Log in `createPeerConnection`: `pc.iceConnectionState`, `pc.connectionState`, `pc.signalingState` changes; log every addIceCandidate failure (`addIceCandidate` returns a promise — catch it).
5. Log in `callPeer`: whether `_roomSubscriptionReady` was true or the call was queued, and queue length.
6. Log received offer/answer/ICE with peerId + sdp length (dedup hits in `_seenSignals` too).

Acceptance: with `?debug=1`, a failed session prints which of H1-H4 it hit. Include these logs in any bug report.

### Phase B — Signaling correctness (the actual media fix)

All in `user/watch_party.js` unless noted.

**B1. Port the bounded `waitForStable` from `js/live_room_client.js:285-301` into `publishLocalTracks()` (1812-1859).**
Replace the unbounded `signalingstatechange` wait with a promise that resolves when stable, **rejects after 2.5s**, and always removes its listener. On timeout: if `signalingState` is `have-local-offer`, continue (we own the offer); otherwise log and proceed without waiting. This kills H3.

**B2. Use the dead `waitForIce(pc)` (1735-1748) in `callPeer()` before `createOffer`.** Wait up to ~3s for `iceGatheringState === 'complete'` (with the existing trickle fallback — if it times out, proceed anyway since trickle ICE is on). Guards against offers created before candidates exist.

**B3. Perfect negotiation in `handleOffer`/`handleAnswer`/`callPeer`.**
Add `this._makingOffer = false` and `this._ignoreOffer = false`. In `callPeer`: set `_makingOffer=true`, `await pc.setLocalDescription()`, `_makingOffer=false`, send offer. In `handleOffer`:
- `offerCollision = this._makingOffer || pc.signalingState !== 'stable'`
- `this._ignoreOffer = !this._isPolitePeer && offerCollision` (polite = `String(this.peerId) < String(fromPeerId)` — same tie-break already used in `onRoomEvent`'s peer-join).
- If `_ignoreOffer`: log and return.
- `await pc.setRemoteDescription(data.sdp)`; if `offerCollision && !polite`, keep the local offer (do nothing more).
- Answer with `_makingOffer=false`; after `setLocalDescription(answer)`, flush `pendingCandidates` for that peer.
- Remove the current `if (pc.signalingState !== 'stable' && pc.currentRemoteDescription) return;` guard (1916-1962) — the new pattern replaces it.
Keep the existing `_seenSignals` dedup (it prevents the Pusher echo loop; do not remove).

**B4. Connection-state recovery.**
In `createPeerConnection` (1750-1810) add:
- `pc.onconnectionstatechange`: if `connectionState === 'failed'` → log, try `pc.restartIce()` once, and after 15s in `failed`/`disconnected` (track with a timer) tear down this PC and call `callPeer(peer)` again to re-offer. Clear the timer on `connected`.
- `pc.oniceconnectionstatechange`: mirror to debug log; update per-peer status text ("connecting…" → connected) for the Phase C readiness.
- Wrap `pc.addIceCandidate` in try/catch + `.catch()` and log failures (B4 of Phase A).
This addresses H2's invisible-stuck state.

**B5. Pusher subscription retry (fixes H1 — the primary suspect).**
In `bindPusherRoom()`:
- On `pusher:subscription_error`: retry with backoff (e.g. 1s, 3s, 8s, cap 30s, max ~6 attempts). Between attempts, `pusher.subscribe(channel)` again (unsubscribe first if Pusher left a dead subscription).
- Also listen to `pusher:connection` → on `connected`, if the channel is not subscribed, re-subscribe; on `unavailable`, surface a visible toast.
- Keep `_pendingPeerCalls` flush on success (already there — verify it runs after retry success).
- On final failure (all retries exhausted): set a flag and show a clear toast "Live connection unavailable — rejoin the room", and do NOT silently pretend success (ties into Phase C readiness).

**B6. Server-side logging in `server.ts` (small).**
In the auth middleware (52-58): log the rejection reason (missing token / expired / bad signature) without the secret. In `join-room` (136-183): log room join/leave counts. Helps Phase E triage.

Acceptance: two browsers on the same machine (or two devices on same Wi-Fi) see each other's video+audio within ~5s of both being in the room; `?debug=1` shows offer → answer → ICE → `connected` for each pair; a forced Pusher subscription failure recovers via retry.

### Phase C — Connecting gate ("only enter the room when everything is connected")

Goal: replace the premature `markConnected()` calls with a real readiness gate + overlay.

**C1. UI overlay in `user/watch_party.php`.**
Next to the `isLeaving` overlay (383-389), add an `isConnecting` full-screen overlay (same visual language — spinner + logo) with:
- `<h2>` "Connecting…" and a `<p>` bound to `connectionHint` (progressive text: "Checking room…" → "Setting up your camera and mic…" → "Connecting to your peers…" → "Establishing secure video link…").
- A "Continue anyway" button shown only after 20s (bound to `showEnterAnyway`), calling `forceEnterRoom()`.
- The overlay must sit **above** the video grid (z-index higher than tiles) and block interaction with media controls while visible.

**C2. Readiness state machine in `watchParty()`.**
Add flags (all `false` initially): `mediaReady`, `presenceReady`, `pusherReady`, `socketReady` (socket.io — **degraded-tolerant**: if Socket.IO handshake fails, treat as ready after a 5s grace since it only carries speaking indicators), `peersReady`.
- `mediaReady` ← set in `startLocalMedia()` after audio acquired AND camera attempt resolved (success **or** denial — denial counts as ready; show "camera off" state).
- `presenceReady` ← after first `join_room.php` success in `announcePresence()`.
- `pusherReady` ← `pusher:subscription_succeeded` (or Phase B5 final failure → ready but with error toast, so the user is not trapped).
- `peersReady` ← true when **every peer in the current roster either has a PC with `connectionState === 'connected'` or has been un-contactable for 20s** (timeout-based, so one dead peer can't trap everyone). Re-evaluated whenever `syncPresence`/`upsertParticipant`/`removePeer` run.
- `_enterWhenReady()`: when all of the above are true → `isConnecting=false` (single place!), update hint.
- **Delete all direct `markConnected()` calls** at init (213, 230, 233) and in `announcePresence` (2441/2446). **Delete `this.isConnecting = false` in `applyMovie` (line 1232)** — movie changes must not fake readiness.
- 20s watchdog from entry: `showEnterAnyway=true`, button appears. `forceEnterRoom()` sets `isConnecting=false` and logs which flags never became ready (debug).
- `markConnected()` (296-300) itself becomes the single `_enterWhenReady()` call site.

**C3. Same gate for host.** The host path shares `init()` → the same flags apply; ensure `peersReady` for the host is true immediately when the roster is empty (host is "connected" once media+presence+pusher are ready), and re-triggers as guests join (peer-join → their PC connects → ready). Guest arrival during host's session must not re-show the overlay.

Acceptance: joining user sees the overlay until their own media is up AND (if peers exist) peer connection established or 20s timeout; host sees overlay only until their own media+presence+pusher are ready; "Continue anyway" always available after 20s; no flicker of overlay on movie change.

### Phase D — Back arrow = clean leave (fixes stale rows + disturbed connection)

Rewrite `goToDashboard()` (451-476):
1. **Host**: if there are other active members, show a confirm dialog: "Leave and end the watch party for everyone?" — confirm → proceed with `endRoomForAll` (send `room-ended` via `room_signal.php`, then leave). Cancel → stay.
2. For everyone: send `peer-leave` via `sendPusherSignal` **and** `leave_room.php` POST (no `park`, no `unload`).
3. Run the same teardown as `leaveRoom()` (385-430): stop all tracks, close every PC, clear `_pendingPeerCalls`, unbind Pusher channel (`pusher.unsubscribe`), disconnect socket.io.
4. `clearNexusActiveRoom()` (or equivalent local cleanup) and remove the `nexus_room_returning` marker behavior that minted a new peerId — after a clean leave, a later rejoin is a **fresh join** (new peer_id is correct and harmless because the old row was deleted).
5. Navigate to `dashboard.php`.
6. Also update the dashboard's `heartbeatActiveRoom` (js/nexus_scripts.js:1974-2015) so a room left via the arrow is **not** listed as active/parked — otherwise the dashboard will show a "rejoin" card for a room you deliberately left.

Acceptance: back arrow → within ~1s the room row disappears from `room_participants` (verify in DB or via the host's participant list), no `peer-park` traffic, dashboard shows no active room, host leaving with guests prompts before ending the party.

### Phase E — Socket.IO / token robustness (the F12 handshake error)

1. In `connectSignaling()` (2539-2585): if `window.NEXUS_SOCKET_TOKEN` is empty, `console.warn` clearly and show a **non-blocking** toast "Voice activity indicators unavailable" (Socket.IO is degraded-optional per C2 — do not block entry).
2. In `watch_party.php`: verify `NEXUS_SOCKET_TOKEN` is embedded for both host and guest (via `socket_auth_helper.php`) and that `NEXUS_SIGNALING_URL` resolves to `''` on Render (same-origin `/socket.io/` proxy) and `hostname:3000` only on non-standard local ports.
3. Ops checklist (no code): confirm on Render that Apache modules `proxy`, `proxy_http`, `proxy_wstunnel`, `rewrite` are enabled (entrypoint does `a2enmod`), `socketio-proxy.conf` is included, and the server is started before Apache. If the deploy shows "Authentication required" with a non-empty token, check `PUSHER_SECRET` matches between PHP and the Node process (`PassEnv` in Dockerfile).
4. `server.ts`: on auth failure, log which check failed (missing/expired/bad HMAC) — done in B6.

### Phase F — Verification checklist (run all; this is the definition of done)

1. **Startup**: `npm run dev` in root → after ~5s `curl -s -o /dev/null -w "%{http_code}" http://localhost:3000/` returns 200. Confirm no unhandled exceptions in the console.
2. **Token handshake** (repeat of verified probe): GET `http://localhost:3000/socket.io/?EIO=4&transport=polling` → take the `sid` from the `open` packet → POST `40{"token":"<NEXUS_SOCKET_TOKEN from the page>"}` → expect `40{"sid":"..."}`, not `44 Authentication required`.
3. **Manual two-browser matrix** (use `?debug=1` in both, capture consoles):
   - Host creates room, guest joins → both see/hear each other within 5s; overlay hides only when connected (or 20s + button).
   - Guest joins **after** host is already streaming (late join) → peer-join → offer → both connected.
   - Camera/mic **denied** in one browser → other side sees placeholder/tile off; no crash; readiness still completes.
   - **3 users** (mesh: each pair must connect — check all 3 pairs).
   - Host presses back arrow with guests → guests get room-ended/leave handling; host's DB row gone; dashboard shows no active room.
   - Guest presses back arrow → host sees them removed; they can rejoin cleanly (fresh peer_id, no duplicate tiles).
   - Refresh mid-call (F5) in one browser → recovers via re-join + `peer-resume`; no duplicate video tiles (verify `restoreOrCreatePeerId` path).
   - Movie change during call → video sync applies, overlay does NOT reappear (C2 regression check).
   - Socket.IO blocked (devtools block `/socket.io/`) → media still works (Pusher path), only speaking indicator missing, entry not blocked.
   - Pusher down (block pusher.com) → toast after retries, "Continue anyway" available, no infinite spinner.
4. **PHP smoke**: `php -l` on every touched file (`user/watch_party.js` is JS — run `node --check`), plus `node --check server.ts` won't work (TS) — run `npx tsc --noEmit` if available, else `npx tsx --eval "import './server.ts'"` is not safe (it listens) — instead start/stop the dev server once and confirm no errors.

---

## Invariants — DO NOT BREAK

1. **Pusher-only SDP signaling.** Never send offer/answer/ICE over Socket.IO (comment at watch_party.js:2111-2114 explains why).
2. **Two-phase media acquisition** (audio first, camera in background, watch_party.js:1631-1713) — keep; it's what makes entry fast.
3. **90-second heartbeat windows** everywhere (`room_participants.last_seen`, pusher_auth membership, join_room sweeping). Any change to polling intervals must stay well under 90s.
4. **Max 5 members / capacity checks** in join_room.php.
5. **`_seenSignals` dedup** (offer dedup) — needed to survive Pusher echoing your own event back; do not remove.
6. **Barba hard navigation** for room/dashboard URLs (`nexusHardNavigateUrl` in js/barba_setup.js) — the room is a full-page app; do not let Barba AJAX it.
7. **`nexus_room_returning` fresh-peerId logic** may stay, but Phase D must delete the old participant row on clean leave so it can't strand stale peers.

## Suggested commit sequence

1. Phase A — "Add debug diagnostics to room client"
2. Phase B — "Fix WebRTC negotiation and Pusher retry in room client" (+ B6 server logging)
3. Phase C — "Gate room entry on connection readiness"
4. Phase D — "Make back arrow a clean room leave"
5. Phase E — "Harden Socket.IO token handling"
6. Run Phase F checklist, then a final commit for any fixes it surfaces.
