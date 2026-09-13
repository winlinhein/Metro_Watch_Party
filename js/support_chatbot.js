function nexusSupportChat() {
    return {
        open: false,
        input: '',
        typing: false,
        unread: true,
        context: { intent: '', genres: [], movie_ids: [] },
        messages: [
            {
                id: 1,
                from: 'bot',
                text: 'Hey! I am Nex. Talk like you would to a friend — “what are you,” “my crush rejected me,” a rainy-night pick, or how rooms and Premium work.',
                movies: []
            }
        ],
        suggestions: [
            'What are you?',
            "It's raining",
            'My crush rejected me',
            'How do I host?'
        ],

        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.unread = false;
                this.$nextTick(() => this.scrollToBottom());
            }
        },

        close() {
            this.open = false;
        },

        async send(preset) {
            const text = String(preset || this.input || '').trim();
            if (!text || this.typing) return;

            this.messages.push({ id: Date.now(), from: 'user', text, movies: [] });
            this.input = '';
            this.typing = true;
            this.$nextTick(() => this.scrollToBottom());

            let reply = 'Give me a second.';
            let movies = [];
            let suggestions = this.suggestions;

            try {
                const res = await fetch('/user_backend/chatbot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        message: text,
                        context: this.context
                    })
                });
                const data = await res.json();
                reply = (data && data.reply) || reply;
                movies = Array.isArray(data && data.movies) ? data.movies : [];
                if (Array.isArray(data && data.suggestions) && data.suggestions.length) {
                    suggestions = data.suggestions;
                }
                this.context = {
                    intent: (data && data.intent) || '',
                    genres: Array.isArray(data && data.genres) ? data.genres : [],
                    movie_ids: movies.map((m) => Number(m.id || m.movie_id) || 0).filter(Boolean)
                };
            } catch (e) {
                reply = this.offlineReply(text);
            }

            window.setTimeout(() => {
                this.typing = false;
                this.suggestions = suggestions;
                this.messages.push({
                    id: Date.now() + 1,
                    from: 'bot',
                    text: reply,
                    movies
                });
                this.$nextTick(() => this.scrollToBottom());
            }, 280);
        },

        dashboard() {
            const root = document.querySelector('[data-barba-namespace="dashboard"]');
            if (!root || !root._x_dataStack || !root._x_dataStack[0]) return null;
            return root._x_dataStack[0];
        },

        openMovie(movie) {
            const dash = this.dashboard();
            this.close();
            if (!dash) return;
            if (typeof dash.switchTab === 'function') dash.switchTab('movies');
            if (typeof dash.openMovieDetail === 'function') {
                dash.openMovieDetail(movie);
            }
        },

        hostMovie(movie) {
            const dash = this.dashboard();
            this.close();
            if (dash && dash.isGuest && typeof dash.requireLogin === 'function') {
                dash.requireLogin('Login to host a party.');
                return;
            }
            if (typeof window.createParty === 'function') {
                window.createParty(movie);
            }
        },

        genreLine(movie) {
            const genres = Array.isArray(movie && movie.genres) ? movie.genres : [];
            return genres.filter(Boolean).slice(0, 3).join(' · ');
        },

        offlineReply(text) {
            const q = String(text || '').toLowerCase();
            if (/(what are you|who are you|are you)/.test(q)) {
                return 'I am Nex, the Nexus guide. Ask me for a movie, how you feel, or how rooms and Premium work.';
            }
            if (/(crush|reject|dumped|break ?up|sad|lonely)/.test(q)) {
                return 'That is rough. I could not reach the catalog just now — try the Drama or Romance rows, or ask me again in a moment.';
            }
            if (/(rain|sad|happy|movie|film|recommend)/.test(q)) {
                return 'I could not reach the catalog just now. Open the Movies tab, or try me again in a moment.';
            }
            if (/(host|room)/.test(q)) {
                return 'Tap Host Party in the bottom-right, pick a title, and share the invite. Friends join the same playhead.';
            }
            if (/(premium)/.test(q)) {
                return 'Premium is $4.99 a month: cosmetics, unlimited hosting, and a Premium badge. Open the Premium tab to unlock.';
            }
            if (/(mission|quest)/.test(q)) {
                return 'Missions reset daily, weekly, or monthly. Hit the target, claim the points, spend them in the Shop.';
            }
            return 'I am Nex. Ask about a movie, the weather, how you feel, hosting, missions, or Premium.';
        },

        scrollToBottom() {
            const el = this.$refs.chatScroll;
            if (el) el.scrollTop = el.scrollHeight;
        }
    };
}
