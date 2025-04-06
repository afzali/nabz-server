import { API_ENDPOINTS, sendRequest } from './api.js';
import { showMessage } from './utils.js';

function initDeleteEvent() {
    // Delete Event
    document.getElementById('deleteEventBtn').addEventListener('click', async () => {
        const eventId = document.getElementById('deleteEventId').value;
        
        if (!eventId) {
            showMessage('Event ID is required', true);
            return;
        }
        
        if (!confirm('Are you sure you want to delete this event?')) {
            return;
        }
        
        try {
            const result = await sendRequest(`${API_ENDPOINTS.EVENTS}/${eventId}`, 'DELETE');
            showMessage('Event deleted successfully!');
            
            // Clear input
            document.getElementById('deleteEventId').value = '';
            
            // Switch to list tab
            document.querySelector('.tab[data-tab="list"]').click();
            // Refresh event list
            document.getElementById('searchBtn').click();
        } catch (error) {
            showMessage(error.message, true);
        }
    });
}

export { initDeleteEvent };
