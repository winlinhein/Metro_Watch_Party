const fs = require('fs');
let readScript = fs.readFileSync('./user_backend/mark_as_read.php', 'utf8');
readScript = readScript.replace(/\$pusher->trigger\((.*?), (.*?), ([\s\S]*?)\);/, 'triggerPusherEvent($1, $2, $3);');
fs.writeFileSync('./user_backend/mark_as_read.php', readScript);

let watchScript = fs.readFileSync('./user_backend/toggle_watchlist.php', 'utf8');
watchScript = watchScript.replace(/\$pusher->trigger\((.*?), (.*?), ([\s\S]*?)\);/, 'triggerPusherEvent($1, $2, $3);');
fs.writeFileSync('./user_backend/toggle_watchlist.php', watchScript);
