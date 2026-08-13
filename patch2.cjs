const fs = require('fs');
let code = fs.readFileSync('js/nexus_scripts.js', 'utf8');

code = code.replace(/initSocket\(\) \{[\s\S]*?const channelName = `user-\${window.CURRENT_USER_ID}`;/m, `initPusher() {
            if (!window.CURRENT_USER_ID || typeof Pusher === 'undefined') return;

            if (!this.pusherClient) {
                this.pusherClient = new Pusher('f4b5637ef4b8952b6eb8', {
                    cluster: 'ap1',
                    encrypted: true
                });
            }   

            const channelName = \`user-\${window.CURRENT_USER_ID}\`;`);

code = code.replace(/window\.socketClient\.emit\('join_chat', channelName\);/m, `const channel = this.pusherClient.subscribe(channelName);`);
code = code.replace(/window\.socketClient\.on\('watchlist-updated'/m, `channel.bind('watchlist-updated'`);
code = code.replace(/window\.socketClient\.on\('friend_event'/m, `channel.bind('friend_event'`);
code = code.replace(/this\.initSocket\(\);/m, `this.initPusher();`);

fs.writeFileSync('js/nexus_scripts.js', code);
