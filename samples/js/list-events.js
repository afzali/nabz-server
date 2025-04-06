import { API_ENDPOINTS, sendRequest } from './api.js';
import { showMessage } from './utils.js';

function initListEvents() {
    // Search Events
    document.getElementById('searchBtn').addEventListener('click', async () => {
        const searchTerm = document.getElementById('searchTerm').value;
        const category = document.getElementById('filterCategory').value;
        const state = document.getElementById('filterState').value;
        
        // Build query parameters
        const params = new URLSearchParams();
        if (searchTerm) params.append('search', searchTerm);
        if (category) params.append('category', category);
        if (state) params.append('state', state);
        
        // Make sure we don't add a question mark if there are no parameters
        const queryString = params.toString();
        const url = queryString ? `${API_ENDPOINTS.EVENTS}?${queryString}` : API_ENDPOINTS.EVENTS;
        
        try {
            const result = await sendRequest(url);
            const eventList = document.getElementById('eventList');
            eventList.innerHTML = '';
            
            if (result.events && result.events.length > 0) {
                result.events.forEach(event => {
                    // Format dates for display
                    const createDate = event.createDate ? new Date(event.createDate).toLocaleString() : 'N/A';
                    const updateDate = event.updateDate ? new Date(event.updateDate).toLocaleString() : 'N/A';
                    const startDate = event.start ? new Date(event.start).toLocaleString() : 'N/A';
                    const endDate = event.end ? new Date(event.end).toLocaleString() : 'N/A';
                    
                    // Format categories
                    let categoryDisplay = 'None';
                    if (event.category) {
                        if (Array.isArray(event.category)) {
                            categoryDisplay = event.category.join(', ');
                        } else {
                            categoryDisplay = event.category;
                        }
                    }
                    
                    // Format tags
                    const tagsDisplay = event.tags && Array.isArray(event.tags) ? event.tags.join(', ') : 'None';
                    
                    // Create event item HTML
                    const eventItem = document.createElement('div');
                    eventItem.className = 'event-item';
                    eventItem.innerHTML = `
                        <h3>${event.title || 'Untitled Event'}</h3>
                        <p><strong>ID:</strong> ${event.id}</p>
                        <p><strong>Description:</strong> ${event.description || 'No description'}</p>
                        <p><strong>Category:</strong> ${categoryDisplay}</p>
                        <p><strong>Tags:</strong> ${tagsDisplay}</p>
                        <p><strong>State:</strong> ${event.state || 'Not specified'}</p>
                        <p><strong>Created:</strong> ${createDate}</p>
                        <p><strong>Updated:</strong> ${updateDate}</p>
                        <p><strong>Start:</strong> ${startDate}</p>
                        <p><strong>End:</strong> ${endDate}</p>
                        <button class="edit-btn" data-id="${event.id}">Edit</button>
                        <button class="delete-btn" data-id="${event.id}">Delete</button>
                    `;
                    
                    eventList.appendChild(eventItem);
                });
                
                // Add event listeners to edit buttons
                document.querySelectorAll('.edit-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        document.getElementById('updateEventId').value = btn.dataset.id;
                        document.querySelector('.tab[data-tab="update"]').click();
                        document.getElementById('loadEventBtn').click();
                    });
                });
                
                // Add event listeners to delete buttons
                document.querySelectorAll('.delete-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        document.getElementById('deleteEventId').value = btn.dataset.id;
                        document.querySelector('.tab[data-tab="delete"]').click();
                    });
                });
            } else {
                eventList.innerHTML = '<p>No events found.</p>';
            }
        } catch (error) {
            showMessage(error.message, true);
        }
    });
}

export { initListEvents };
