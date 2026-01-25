<footer class="p-4 text-center text-sm text-gray-600">
    <p>&copy; {{ date('Y') }} MIS of MHO Carmen · For authorized use only · Protected under RA 10173 (Data Privacy Act) · All rights reserved.</p>
</footer>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('open-pdf-in-new-tab', (event) => {
            // Handle both Livewire v2 and v3 event formats
            let url = null;
            
            if (Array.isArray(event)) {
                url = event[0]?.url || event[0];
            } else if (typeof event === 'object' && event !== null) {
                url = event.url || event;
            } else if (typeof event === 'string') {
                url = event;
            }
            
            if (url) {
                window.open(url, '_blank');
            }
        });
    });
    
    // Also listen for the event after Livewire is loaded (fallback)
    document.addEventListener('livewire:load', () => {
        Livewire.on('open-pdf-in-new-tab', (event) => {
            let url = null;
            
            if (Array.isArray(event)) {
                url = event[0]?.url || event[0];
            } else if (typeof event === 'object' && event !== null) {
                url = event.url || event;
            } else if (typeof event === 'string') {
                url = event;
            }
            
            if (url) {
                window.open(url, '_blank');
            }
        });
    });
</script>