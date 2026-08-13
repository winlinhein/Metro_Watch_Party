const fs = require('fs');
let content = fs.readFileSync('./user_backend/mark_as_read.php', 'utf8');

content = content.replace(/\$reader_id = \$_POST\['reader_id'\];/g, '$reader_id = $currentUserId;');
content = content.replace(/\$sender_id = \$_POST\['sender_id'\];/g, '$sender_id = $senderId;');

fs.writeFileSync('./user_backend/mark_as_read.php', content);
