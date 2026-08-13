const fs = require('fs');
let c = fs.readFileSync('./user_backend/send_chat.php', 'utf8');
c = c.replace(/if\s*\(isset\(\$pusher\)\)\s*\{[\s\S]*?\}/, 'triggerPusherEvent($channelName, "new_message", $payload);');
fs.writeFileSync('./user_backend/send_chat.php', c);
