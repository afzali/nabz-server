import { API_ENDPOINTS, sendRequest } from './api.js';
import { showMessage } from './utils.js';

// Variables for update form
let updateTags = [];
let updateCategories = [];
let updateFeedbackOptions = [];

function initUpdateEvent() {
    const updateTagsContainer = document.getElementById('updateTagsContainer');
    const updateTagInput = document.getElementById('updateTagInput');
    
    const updateCategoriesContainer = document.getElementById('updateCategoriesContainer');
    const updateCategoryInput = document.getElementById('updateCategoryInput');
    
    const updateFeedbackContainer = document.getElementById('updateFeedbackContainer');
    const updateFeedbackInput = document.getElementById('updateFeedbackInput');
    
    // Render functions for update form
    function renderUpdateTags() {
        // Clear existing tags (except input)
        Array.from(updateTagsContainer.children).forEach(child => {
            if (child !== updateTagInput) {
                updateTagsContainer.removeChild(child);
            }
        });
        
        // Add tags
        updateTags.forEach((tag, index) => {
            const tagElement = document.createElement('div');
            tagElement.className = 'tag';
            tagElement.innerHTML = `${tag} <span class="tag-remove" data-index="${index}">×</span>`;
            updateTagsContainer.insertBefore(tagElement, updateTagInput);
        });
    }
    
    function renderUpdateCategories() {
        // Clear existing categories (except input)
        Array.from(updateCategoriesContainer.children).forEach(child => {
            if (child !== updateCategoryInput) {
                updateCategoriesContainer.removeChild(child);
            }
        });
        
        // Add categories
        updateCategories.forEach((category, index) => {
            const categoryElement = document.createElement('div');
            categoryElement.className = 'tag';
            categoryElement.innerHTML = `${category} <span class="tag-remove" data-index="${index}">×</span>`;
            updateCategoriesContainer.insertBefore(categoryElement, updateCategoryInput);
        });
    }
    
    function renderUpdateFeedbackOptions() {
        // Clear existing feedback options (except input)
        Array.from(updateFeedbackContainer.children).forEach(child => {
            if (child !== updateFeedbackInput) {
                updateFeedbackContainer.removeChild(child);
            }
        });
        
        // Add feedback options
        updateFeedbackOptions.forEach((option, index) => {
            const optionElement = document.createElement('div');
            optionElement.className = 'tag';
            optionElement.innerHTML = `${option} <span class="tag-remove" data-index="${index}">×</span>`;
            updateFeedbackContainer.insertBefore(optionElement, updateFeedbackInput);
        });
    }
    
    // Event listeners for update form
    updateTagInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && updateTagInput.value.trim()) {
            e.preventDefault();
            if (updateTags.length < 7) { // Limit to 7 tags
                updateTags.push(updateTagInput.value.trim());
                updateTagInput.value = '';
                renderUpdateTags();
            } else {
                showMessage('Maximum 7 tags allowed', true);
            }
        }
    });
    
    updateTagsContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('tag-remove')) {
            const index = parseInt(e.target.dataset.index);
            updateTags.splice(index, 1);
            renderUpdateTags();
        }
    });
    
    updateCategoryInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && updateCategoryInput.value.trim()) {
            e.preventDefault();
            updateCategories.push(updateCategoryInput.value.trim());
            updateCategoryInput.value = '';
            renderUpdateCategories();
        }
    });
    
    updateCategoriesContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('tag-remove')) {
            const index = parseInt(e.target.dataset.index);
            updateCategories.splice(index, 1);
            renderUpdateCategories();
        }
    });
    
    updateFeedbackInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && updateFeedbackInput.value.trim()) {
            e.preventDefault();
            updateFeedbackOptions.push(updateFeedbackInput.value.trim());
            updateFeedbackInput.value = '';
            renderUpdateFeedbackOptions();
        }
    });
    
    updateFeedbackContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('tag-remove')) {
            const index = parseInt(e.target.dataset.index);
            updateFeedbackOptions.splice(index, 1);
            renderUpdateFeedbackOptions();
        }
    });
    
    // Handle repetition type changes for update form
    document.getElementById('updateRepetitionType').addEventListener('change', function() {
        const details = document.getElementById('updateRepetitionDetails');
        const daysContainer = document.getElementById('updateRepetitionDaysContainer');
        
        if (this.value) {
            details.style.display = 'block';
            if (this.value === 'weekly') {
                daysContainer.style.display = 'block';
            } else {
                daysContainer.style.display = 'none';
            }
        } else {
            details.style.display = 'none';
            daysContainer.style.display = 'none';
        }
    });
    
    // Handle notification add button for update form
    document.querySelector('.add-update-notif').addEventListener('click', function() {
        const container = document.getElementById('updateNotifContainer');
        const newItem = document.createElement('div');
        newItem.className = 'notif-item';
        newItem.innerHTML = `
            <input type="datetime-local" class="update-notif-time">
            <button type="button" class="remove-update-notif">-</button>
        `;
        container.appendChild(newItem);
        
        // Add event listener to the new remove button
        newItem.querySelector('.remove-update-notif').addEventListener('click', function() {
            container.removeChild(newItem);
        });
    });
    
    // Event delegation for dynamically added remove buttons
    document.getElementById('updateNotifContainer').addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-update-notif')) {
            const item = e.target.parentNode;
            this.removeChild(item);
        }
    });
    
    // Load Event for Update
    document.getElementById('loadEventBtn').addEventListener('click', async () => {
        const eventId = document.getElementById('updateEventId').value;
        
        if (!eventId) {
            showMessage('Event ID is required', true);
            return;
        }
        
        try {
            const result = await sendRequest(`${API_ENDPOINTS.EVENTS}/${eventId}`);
            const event = result.event;
            
            // Reset all arrays
            updateTags = [];
            updateCategories = [];
            updateFeedbackOptions = [];
            
            // Populate form fields
            document.getElementById('updateTitle').value = event.title || '';
            document.getElementById('updateDescription').value = event.description || '';
            document.getElementById('updateIcon').value = event.icon || '';
            
            // Handle category (could be string or array)
            if (event.category) {
                if (Array.isArray(event.category)) {
                    updateCategories = [...event.category];
                } else if (typeof event.category === 'string') {
                    try {
                        // Try to parse as JSON
                        const parsed = JSON.parse(event.category);
                        if (Array.isArray(parsed)) {
                            updateCategories = [...parsed];
                        } else {
                            updateCategories = [event.category];
                        }
                    } catch (e) {
                        // Not JSON, treat as string
                        updateCategories = [event.category];
                    }
                }
            }
            renderUpdateCategories();
            
            // Handle tags
            if (event.tags && Array.isArray(event.tags)) {
                updateTags = [...event.tags];
                renderUpdateTags();
            }
            
            // Handle dates
            if (event.start) {
                const startDate = new Date(event.start);
                document.getElementById('updateStart').value = startDate.toISOString().slice(0, 16);
            }
            
            if (event.end) {
                const endDate = new Date(event.end);
                document.getElementById('updateEnd').value = endDate.toISOString().slice(0, 16);
            }
            
            document.getElementById('updateState').value = event.state || 'not done';
            document.getElementById('updateCount').value = event.count || '';
            document.getElementById('updateCountUnit').value = event.countUnit || '';
            document.getElementById('updateCountCondition').value = event.countCondition || '';
            document.getElementById('updateTimeCondition').value = event.timeCondition || '';
            document.getElementById('updateDurationCondition').value = event.durationCondition || '';
            
            // Handle feedback options
            if (event.feedback && Array.isArray(event.feedback)) {
                updateFeedbackOptions = [...event.feedback];
                renderUpdateFeedbackOptions();
            }
            
            // Handle notifications
            const notifContainer = document.getElementById('updateNotifContainer');
            notifContainer.innerHTML = ''; // Clear existing notifications
            
            if (event.notif && Array.isArray(event.notif)) {
                event.notif.forEach((notifTime, index) => {
                    const notifItem = document.createElement('div');
                    notifItem.className = 'notif-item';
                    
                    const timeInput = document.createElement('input');
                    timeInput.type = 'datetime-local';
                    timeInput.className = 'update-notif-time';
                    
                    try {
                        const notifDate = new Date(notifTime);
                        timeInput.value = notifDate.toISOString().slice(0, 16);
                    } catch (e) {
                        console.error('Invalid notification time:', notifTime);
                    }
                    
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = index === 0 ? 'add-update-notif' : 'remove-update-notif';
                    btn.textContent = index === 0 ? '+' : '-';
                    
                    notifItem.appendChild(timeInput);
                    notifItem.appendChild(btn);
                    notifContainer.appendChild(notifItem);
                });
            } else {
                // Add default empty notification input
                const notifItem = document.createElement('div');
                notifItem.className = 'notif-item';
                notifItem.innerHTML = `
                    <input type="datetime-local" class="update-notif-time">
                    <button type="button" class="add-update-notif">+</button>
                `;
                notifContainer.appendChild(notifItem);
            }
            
            // Handle repetition
            const repetitionType = document.getElementById('updateRepetitionType');
            const repetitionDetails = document.getElementById('updateRepetitionDetails');
            const repetitionDaysContainer = document.getElementById('updateRepetitionDaysContainer');
            
            if (event.repetition) {
                let repetition;
                if (typeof event.repetition === 'string') {
                    try {
                        repetition = JSON.parse(event.repetition);
                    } catch (e) {
                        repetition = null;
                    }
                } else {
                    repetition = event.repetition;
                }
                
                if (repetition && repetition.type) {
                    repetitionType.value = repetition.type;
                    repetitionDetails.style.display = 'block';
                    
                    if (repetition.interval) {
                        document.getElementById('updateRepetitionInterval').value = repetition.interval;
                    }
                    
                    if (repetition.type === 'weekly' && repetition.days && Array.isArray(repetition.days)) {
                        repetitionDaysContainer.style.display = 'block';
                        
                        // Reset all checkboxes
                        document.querySelectorAll('.update-rep-day').forEach(cb => {
                            cb.checked = false;
                        });
                        
                        // Check the appropriate days
                        repetition.days.forEach(day => {
                            const checkbox = document.querySelector(`.update-rep-day[value="${day}"]`);
                            if (checkbox) {
                                checkbox.checked = true;
                            }
                        });
                    } else {
                        repetitionDaysContainer.style.display = 'none';
                    }
                } else {
                    repetitionType.value = '';
                    repetitionDetails.style.display = 'none';
                    repetitionDaysContainer.style.display = 'none';
                }
            } else {
                repetitionType.value = '';
                repetitionDetails.style.display = 'none';
                repetitionDaysContainer.style.display = 'none';
            }
            
            showMessage('Event loaded successfully');
        } catch (error) {
            showMessage(error.message, true);
        }
    });
    
    // Update Event
    document.getElementById('updateEventForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const eventId = document.getElementById('updateEventId').value;
        
        if (!eventId) {
            showMessage('Event ID is required', true);
            return;
        }
        
        // Format datetime values to ISO 8601
        const startDate = document.getElementById('updateStart').value ? 
            new Date(document.getElementById('updateStart').value).toISOString() : null;
            
        const endDate = document.getElementById('updateEnd').value ? 
            new Date(document.getElementById('updateEnd').value).toISOString() : null;
        
        // Collect notification times
        const notifTimes = [];
        document.querySelectorAll('.update-notif-time').forEach(input => {
            if (input.value) {
                notifTimes.push(new Date(input.value).toISOString());
            }
        });
        
        // Build repetition object
        let repetition = null;
        const repetitionType = document.getElementById('updateRepetitionType').value;
        
        if (repetitionType) {
            repetition = {
                type: repetitionType,
                interval: parseInt(document.getElementById('updateRepetitionInterval').value) || 1
            };
            
            if (repetitionType === 'weekly') {
                const days = [];
                document.querySelectorAll('.update-rep-day:checked').forEach(cb => {
                    days.push(parseInt(cb.value));
                });
                repetition.days = days;
            }
        }
        
        // Build event data
        const eventData = {
            title: document.getElementById('updateTitle').value || null,
            description: document.getElementById('updateDescription').value || null,
            category: updateCategories.length > 0 ? updateCategories : null,
            icon: document.getElementById('updateIcon').value || null,
            tags: updateTags.length > 0 ? updateTags : null,
            start: startDate,
            end: endDate,
            state: document.getElementById('updateState').value,
            count: document.getElementById('updateCount').value ? parseInt(document.getElementById('updateCount').value) : null,
            countUnit: document.getElementById('updateCountUnit').value || null,
            countCondition: document.getElementById('updateCountCondition').value || null,
            timeCondition: document.getElementById('updateTimeCondition').value || null,
            durationCondition: document.getElementById('updateDurationCondition').value || null,
            feedback: updateFeedbackOptions.length > 0 ? updateFeedbackOptions : null,
            notif: notifTimes.length > 0 ? notifTimes : null,
            repetition: repetition,
            updateDate: new Date().toISOString()
        };
        
        try {
            const result = await sendRequest(`${API_ENDPOINTS.EVENTS}/${eventId}`, 'PUT', eventData);
            showMessage('Event updated successfully!');
            
            // Clear form
            document.getElementById('updateEventForm').reset();
            document.getElementById('updateEventId').value = '';
            updateTags = [];
            updateCategories = [];
            updateFeedbackOptions = [];
            renderUpdateTags();
            renderUpdateCategories();
            renderUpdateFeedbackOptions();
            
            // Switch to list tab
            document.querySelector('.tab[data-tab="list"]').click();
            // Refresh event list
            document.getElementById('searchBtn').click();
        } catch (error) {
            showMessage(error.message, true);
        }
    });
}

export { initUpdateEvent };
