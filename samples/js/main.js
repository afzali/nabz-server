import { updateAuthStatus, initAuth } from './auth.js';
import { initTabs } from './utils.js';
import { initCreateEvent } from './create-event.js';
import { initListEvents } from './list-events.js';
import { initUpdateEvent } from './update-event.js';
import { initDeleteEvent } from './delete-event.js';

// Initialize all modules when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Initialize auth status
    updateAuthStatus();
    
    // Initialize tabs
    initTabs();
    
    // Initialize auth module
    initAuth();
    
    // Initialize event modules
    initCreateEvent();
    initListEvents();
    initUpdateEvent();
    initDeleteEvent();
});
