<?php
$content = file_get_contents('./user_backend/send_chat.php');
$content = str_replace(
"if (isset(\$pusher)) {\n    \$pusher->trigger(\$channelName, 'new_message', \$payload);\n}",
"// Trigger custom pusher event\ntriggerPusherEvent(\$channelName, 'new_message', \$payload);",
$content);
file_put_contents('./user_backend/send_chat.php', $content);
