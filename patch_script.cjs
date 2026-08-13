const fs = require('fs');
let code = fs.readFileSync('js/nexus_scripts.js', 'utf8');

// State
code = code.replace(/\/\/ Socket Client[\s\S]*?\/\/ Add channel reference/, "// Pusher Client\n        pusherClient: null,\n\n        // Add channel reference");

// initChatSubscription
code = code.replace(/initChatSubscription\(friendId\) \{[\s\S]*?initAllChatSubscriptions\(\) \{/m, `initChatSubscription(friendId) {
            const targetFriendId = Number(friendId);
            const currentUserId = Number(window.CURRENT_USER_ID);

            if (typeof Pusher === 'undefined' || !currentUserId || !targetFriendId) return;

            if (!this.pusherClient) {
                this.pusherClient = new Pusher('f4b5637ef4b8952b6eb8', { cluster: 'ap1', encrypted: true });
            }

            const minId = Math.min(currentUserId, targetFriendId);
            const maxId = Math.max(currentUserId, targetFriendId);
            const channelName = \`chat-\${minId}-\${maxId}\`;

            if (this.activeSubscriptions.has(channelName)) return;
            this.activeSubscriptions.add(channelName);

            const channel = this.pusherClient.subscribe(channelName);

           channel.bind('new_message', (data) => {
                const senderId = Number(data.sender_id);
                if (senderId === Number(window.CURRENT_USER_ID)) return;

                const activeFriendId = Number(this.activeChatFriend?.user_id || this.activeChatFriend?.friend_id || this.activeChatFriend?.id);
                const isCurrentActiveChat = this.showChatPanel && activeFriendId === senderId;

                if (isCurrentActiveChat) {
                    this.chatMessages = [...this.chatMessages, {
                        id: data.id || 'live-' + Date.now(), 
                        sender: 'them',
                        text: data.message_text,
                        time: this.formatTime(data.time)
                    }];
                    this.scrollToBottom();

                    fetch('/user_backend/mark_as_read.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ sender_id: senderId })
                    });
                } else {
                    const friendItem = this.friends.find(f => Number(f.user_id || f.friend_id || f.id) === senderId);
                    if (friendItem) {
                        friendItem.unread_count = (Number(friendItem.unread_count) || 0) + 1;
                    }
                }
            });

            channel.bind('messages_read', (data) => {
                const activeFriendId = Number(this.activeChatFriend?.user_id || this.activeChatFriend?.friend_id || this.activeChatFriend?.id);
                if (Number(data.reader_id) === activeFriendId) {
                    this.chatMessages.forEach(msg => {
                        if (msg.sender === 'me') msg.is_read = 1;
                    });
                }
            });
        },

        initAllChatSubscriptions() {`);

// sendChat
code = code.replace(/\/\/ Emit via socket\.io[\s\S]*?try \{/m, "try {");

// subscribeToLiveMovieEvents
code = code.replace(/subscribeToLiveMovieEvents\(movieId\) \{[\s\S]*?unsubscribeFromLiveMovieEvents\(movieId\) \{/m, `subscribeToLiveMovieEvents(movieId) {
            if (typeof Pusher === 'undefined') return;

            // Initialize Pusher client once if not active
            if (!this.pusherClient) {
                this.pusherClient = new Pusher('f4b5637ef4b8952b6eb8', {
                    cluster: 'ap1',
                    encrypted: true
                });
            }

            const channelName = \`movie-\${movieId}\`;

            // Clean up previous movie channel before subscribing to a new one
            if (this.activeMovieChannel) {
                this.pusherClient.unsubscribe(this.activeMovieChannel.name);
                this.activeMovieChannel = null;
            }

            // Subscribe to new channel
            const channel = this.pusherClient.subscribe(channelName);
            this.activeMovieChannel = channel;

            // 1. Live Ratings Update
            channel.bind('rating_updated', (data) => {
                if (!this.selectedMovie || Number(this.getMovieId(this.selectedMovie)) !== Number(data.movie_id)) return;

                // Directly update properties on the reactive proxy instead of reassigning the whole object
                this.selectedMovie.rating = data.avg_rating;
                this.selectedMovie.rating_count = data.rating_count;

                // Sync main movies array
                const movieInList = this.movies.find(m => Number(this.getMovieId(m)) === Number(data.movie_id));
                if (movieInList) {
                    movieInList.rating = data.avg_rating;
                    movieInList.rating_count = data.rating_count;
                }
            });

            // 2. Live Comment Update
            channel.bind('new_comment', (data) => {
                if (!this.selectedMovie || Number(this.getMovieId(this.selectedMovie)) !== Number(data.movie_id)) return;

                const currentComments = this.selectedMovie.comments || [];
                // Push onto the array
                this.selectedMovie.comments = [data, ...currentComments];
            });

            // 3. Live Reply Update
           channel.bind('new_reply', (data) => {
                if (!this.selectedMovie || Number(this.getMovieId(this.selectedMovie)) !== Number(data.movie_id)) return;

                // Build the new comments array
                const updatedComments = this.selectedMovie.comments.map(comment => {
                    if (Number(comment.id) === Number(data.parent_id)) {
                        const currentReplies = comment.replies || [];
                        const replyExists = currentReplies.some(r => Number(r.id) === Number(data.id));

                        if (!replyExists) {
                            return { ...comment, replies: [...currentReplies, data] };
                        }
                    }
                    return comment;
                });

                // OVERWRITE the root object
                this.selectedMovie = {
                    ...this.selectedMovie,
                    comments: updatedComments
                };
            });

            // 4. Live Like Update
            channel.bind('comment_liked', (data) => {
                if (!this.selectedMovie || Number(this.getMovieId(this.selectedMovie)) !== Number(data.movie_id)) return;

                const findAndSetLikes = (list) => {
                    if (!list) return false;
                    for (let i = 0; i < list.length; i++) {
                        if (Number(list[i].id) === Number(data.comment_id)) {
                            list[i].likes = data.likes;
                            return true;
                        }
                        if (list[i].replies && findAndSetLikes(list[i].replies)) {
                            return true;
                        }
                    }
                    return false;
                };

                // Create a shallow copy of comments to force reactivity
                const newComments = [...(this.selectedMovie.comments || [])];
                if (findAndSetLikes(newComments)) {
                    this.selectedMovie.comments = newComments;
                }
            });
        },

        unsubscribeFromLiveMovieEvents(movieId) {`);

// unsubscribeFromLiveMovieEvents
code = code.replace(/unsubscribeFromLiveMovieEvents\(movieId\) \{[\s\S]*?markNotificationsAsRead/m, `unsubscribeFromLiveMovieEvents(movieId) {
            if (this.pusherClient && movieId) {
                this.pusherClient.unsubscribe(\`movie-\${movieId}\`);
                this.activeMovieChannel = null;
            }
        },

        markNotificationsAsRead`);

// initPusher
code = code.replace(/\/\/ Initialize Socket Connection[\s\S]*?\/\/ 1\. Update the main/m, `// Initialize Pusher Connection
        initPusher() {
            if (!window.CURRENT_USER_ID || typeof Pusher === 'undefined') return;

                if (!this.pusherClient) {
                    this.pusherClient = new Pusher('f4b5637ef4b8952b6eb8', {
                        cluster: 'ap1',
                        encrypted: true
                    });
                }   

                // Subscribing using the correctly injected PHP variable
                const channel = this.pusherClient.subscribe(\`user-\${window.CURRENT_USER_ID}\`);

                channel.bind('watchlist-updated', (data) => {
                    const movieId = data.movie_id;
                    const action = data.action;

                // 1. Update the main`);

// the rest of initPusher
code = code.replace(/window\.socketClient\.on\('friend_event', \(data\) => \{/m, `channel.bind('friend_event', (data) => {`);

code = code.replace(/this\.initSocket\(\);/m, `this.initPusher();`);

fs.writeFileSync('js/nexus_scripts.js', code);
