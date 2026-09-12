function nexusSupportChat() {
    return {
        open: false,
        input: '',
        typing: false,
        unread: true,
        messages: [
            {
                id: 1,
                from: 'bot',
                text: 'Hey! I am Nex, your watch-party guide. Ask me how to host a room, find a movie, or get around the dashboard.'
            }
        ],
        suggestions: [
            'How do I host a party?',
            'Where are movies?',
            'What is Premium?'
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

        send(preset) {
            const text = String(preset || this.input || '').trim();
            if (!text || this.typing) return;

            this.messages.push({ id: Date.now(), from: 'user', text });
            this.input = '';
            this.typing = true;
            this.$nextTick(() => this.scrollToBottom());

            const reply = this.replyFor(text);
            window.setTimeout(() => {
                this.typing = false;
                this.messages.push({ id: Date.now() + 1, from: 'bot', text: reply });
                this.$nextTick(() => this.scrollToBottom());
            }, 650);
        },

        replyFor(text) {
            const q = text.toLowerCase();
            if (/(hello|hi|hey|yo)\b/.test(q)) {
                return 'Hey there. I can walk you through hosting a party, finding movies, Premium, friends, or your account.';
            }
            if (/(host|create).*(party|room)|start.*(watch|party)|how.*party/.test(q)) {
                return 'Tap Host Party in the bottom-right, pick a title, then share the invite. Friends can jump in from the room link.';
            }
            if (/(movie|film|watchlist|catalog|library)/.test(q)) {
                return 'Open the Movies tab to browse the catalog, or use Watchlist to keep titles you want to play later.';
            }
            if (/(premium|plan|upgrade|subscribe|payment)/.test(q)) {
                return 'Premium unlocks extra perks on the Premium tab. Guests can start free from the plan card; members can manage their plan there.';
            }
            if (/(friend|invite|chat|message)/.test(q)) {
                return 'Open Friends from the header to add people, send invites, and start a private chat.';
            }
            if (/(quest|point|shop|reward)/.test(q)) {
                return 'Quests live in the right-hand drawer. Finish daily, weekly, or monthly tasks to earn points you can spend in the Shop.';
            }
            if (/(account|profile|avatar|setting)/.test(q)) {
                return 'Your avatar and account controls are in the header profile menu. The Account tab has the rest of your settings.';
            }
            if (/(help|support|what can you)/.test(q)) {
                return 'I can help with parties, movies, Premium, friends, quests, and your account. Try one of the suggestions below.';
            }
            return 'I am a local guide for now, so I cannot look that up live. Try asking about hosting, movies, Premium, friends, or quests.';
        },

        scrollToBottom() {
            const el = this.$refs.chatScroll;
            if (el) el.scrollTop = el.scrollHeight;
        }
    };
}
