<!-- Fixed positioning to keep it floating at the bottom right -->
<div class="fixed bottom-8 right-8 z-[100]" x-show="!showMovieDetailModal" x-transition>
    
    <!-- Alpine @click instantly generates a random ID and redirects -->
    <button @click="createParty()" 
            class="group relative flex items-center justify-center gap-2 bg-nexus-red hover:bg-red-400 text-white px-6 py-4 rounded-full font-bold shadow-[0_0_20px_rgba(239,68,68,0.4)] hover:shadow-[0_0_40px_rgba(239,68,68,0.6)] transition-all duration-300 hover:-translate-y-1 overflow-hidden">
        
        <!-- Add Icon -->
        <span class="material-symbols-outlined text-[24px] group-hover:rotate-90 transition-transform duration-300">add</span>
        
        <!-- Text -->
        <span class="tracking-wide">Host Party</span>
        
        <!-- Hover Highlight Effect -->
        <div class="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-300 ease-in-out rounded-full pointer-events-none"></div>
    </button>
    
    <!-- Pulse ring behind the button -->
    <div class="absolute inset-0 bg-red-500 rounded-full animate-ping opacity-20 -z-10 pointer-events-none" style="animation-duration: 2s;"></div>
</div>

<script>
    async function createParty() {
        try {
            // 1. Create the data payload that your PHP script is looking for
            const formData = new FormData();
            formData.append('action', 'create_room');

            // 2. Send the POST request with the data attached
            const response = await fetch('../user_backend/create_room.php', { 
                method: 'POST',
                body: formData
            });
            
            const data = await response.json(); 
            
            // 3. Check for success before redirecting
            if (data.success) {
                console.log(`Room created successfully! Code: ${data.room_code}`);
                window.location.href = `watch_party.php?room_id=${data.room_id}`;
            } else {
                console.error("Room creation failed:", data.error);
                if (window.showToast) window.showToast(data.error, 'error');
            }
        } catch (error) {
            console.error("Error creating room:", error);
        }
    }
</script>