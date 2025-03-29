import { API_ENDPOINTS, sendRequest } from './api.js';
import { showMessage } from './utils.js';

// Variables for create form
let tags = [];
let categories = [];
let feedbackOptions = [];

function initCreateEvent() {
    const tagsContainer = document.getElementById('tagsContainer');
    const tagInput = document.getElementById('tagInput');
    
    const categoriesContainer = document.getElementById('categoriesContainer');
    const categoryInput = document.getElementById('categoryInput');
    
    const feedbackContainer = document.getElementById('feedbackContainer');
    const feedbackInput = document.getElementById('feedbackInput');
    
    // Render functions
    function renderTags() {
        // Clear existing tags (except input)
        Array.from(tagsContainer.children).forEach(child => {
            if (child !== tagInput) {
                tagsContainer.removeChild(child);
            }
        });
        
        // Add tags
        tags.forEach((tag, index) => {
            const tagElement = document.createElement('div');
            tagElement.className = 'tag';
            tagElement.innerHTML = `${tag} <span class="tag-remove" data-index="${index}">×</span>`;
            tagsContainer.insertBefore(tagElement, tagInput);
        });
    }
    
    function renderCategories() {
        // Clear existing categories (except input)
        Array.from(categoriesContainer.children).forEach(child => {
            if (child !== categoryInput) {
                categoriesContainer.removeChild(child);
            }
        });
        
        // Add categories
        categories.forEach((category, index) => {
            const categoryElement = document.createElement('div');
            categoryElement.className = 'tag';
            categoryElement.innerHTML = `${category} <span class="tag-remove" data-index="${index}">×</span>`;
            categoriesContainer.insertBefore(categoryElement, categoryInput);
        });
    }
    
    function renderFeedbackOptions() {
        // Clear existing feedback options (except input)
        Array.from(feedbackContainer.children).forEach(child => {
            if (child !== feedbackInput) {
                feedbackContainer.removeChild(child);
            }
        });
        
        // Add feedback options
        feedbackOptions.forEach((option, index) => {
            const optionElement = document.createElement('div');
            optionElement.className = 'tag';
            optionElement.innerHTML = `${option} <span class="tag-remove" data-index="${index}">×</span>`;
            feedbackContainer.insertBefore(optionElement, feedbackInput);
        });
    }
    
    // Event listeners
    tagInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && tagInput.value.trim()) {
            e.preventDefault();
            if (tags.length < 7) { // Limit to 7 tags
                tags.push(tagInput.value.trim());
                tagInput.value = '';
                renderTags();
            } else {
                showMessage('Maximum 7 tags allowed', true);
            }
        }
    });
    
    tagsContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('tag-remove')) {
            const index = parseInt(e.target.dataset.index);
            tags.splice(index, 1);
            renderTags();
        }
    });
    
    categoryInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && categoryInput.value.trim()) {
            e.preventDefault();
            categories.push(categoryInput.value.trim());
            categoryInput.value = '';
            renderCategories();
        }
    });
    
    categoriesContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('tag-remove')) {
            const index = parseInt(e.target.dataset.index);
            categories.splice(index, 1);
            renderCategories();
        }
    });
    
    feedbackInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && feedbackInput.value.trim()) {
            e.preventDefault();
            feedbackOptions.push(feedbackInput.value.trim());
            feedbackInput.value = '';
            renderFeedbackOptions();
        }
    });
    
    feedbackContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('tag-remove')) {
            const index = parseInt(e.target.dataset.index);
            feedbackOptions.splice(index, 1);
            renderFeedbackOptions();
        }
    });
    
    // Handle repetition type changes
    document.getElementById('repetitionType').addEventListener('change', function() {
        const details = document.getElementById('repetitionDetails');
        const daysContainer = document.getElementById('repetitionDaysContainer');
        
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
    
    // Handle notification add button
    document.querySelector('.add-notif').addEventListener('click', function() {
        const container = document.getElementById('notifContainer');
        const newItem = document.createElement('div');
        newItem.className = 'notif-item';
        newItem.innerHTML = `
            <input type="datetime-local" class="notif-time">
            <button type="button" class="remove-notif">-</button>
        `;
        container.appendChild(newItem);
        
        // Add event listener to the new remove button
        newItem.querySelector('.remove-notif').addEventListener('click', function() {
            container.removeChild(newItem);
        });
    });
    
    // Event delegation for dynamically added remove buttons
    document.getElementById('notifContainer').addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-notif')) {
            const item = e.target.parentNode;
            this.removeChild(item);
        }
    });
    
    // Create Event Form Submit
    document.getElementById('createEventForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // Format datetime values to ISO 8601
        const startDate = document.getElementById('start').value ? 
            new Date(document.getElementById('start').value).toISOString() : null;
            
        const endDate = document.getElementById('end').value ? 
            new Date(document.getElementById('end').value).toISOString() : null;
        
        // Collect notification times
        const notifTimes = [];
        document.querySelectorAll('.notif-time').forEach(input => {
            if (input.value) {
                notifTimes.push(new Date(input.value).toISOString());
            }
        });
        
        // Build repetition object
        let repetition = null;
        const repetitionType = document.getElementById('repetitionType').value;
        
        if (repetitionType) {
            repetition = {
                type: repetitionType,
                interval: parseInt(document.getElementById('repetitionInterval').value) || 1
            };
            
            if (repetitionType === 'weekly') {
                const days = [];
                document.querySelectorAll('#repetitionDaysContainer input[type="checkbox"]:checked').forEach(cb => {
                    days.push(parseInt(cb.value));
                });
                repetition.days = days;
            }
        }
        
        // Build event data
        const eventData = {
            title: document.getElementById('title').value || null,
            description: document.getElementById('description').value || null,
            category: categories.length > 0 ? categories : null,
            icon: document.getElementById('icon').value || null,
            tags: tags.length > 0 ? tags : null,
            start: startDate,
            end: endDate,
            state: document.getElementById('state').value,
            count: document.getElementById('count').value ? parseInt(document.getElementById('count').value) : null,
            countUnit: document.getElementById('countUnit').value || null,
            countCondition: document.getElementById('countCondition').value || null,
            timeCondition: document.getElementById('timeCondition').value || null,
            durationCondition: document.getElementById('durationCondition').value || null,
            feedback: feedbackOptions.length > 0 ? feedbackOptions : null,
            notif: notifTimes.length > 0 ? notifTimes : null,
            repetition: repetition
        };
        
        try {
            const result = await sendRequest(API_ENDPOINTS.EVENTS, 'POST', eventData);
            showMessage('Event created successfully!');
            
            // Clear form
            document.getElementById('createEventForm').reset();
            tags = [];
            categories = [];
            feedbackOptions = [];
            renderTags();
            renderCategories();
            renderFeedbackOptions();
            
            // Reset repetition
            document.getElementById('repetitionDetails').style.display = 'none';
            document.getElementById('repetitionDaysContainer').style.display = 'none';
            
            // Reset notifications
            const notifContainer = document.getElementById('notifContainer');
            notifContainer.innerHTML = `
                <div class="notif-item">
                    <input type="datetime-local" class="notif-time">
                    <button type="button" class="add-notif">+</button>
                </div>
            `;
            
            // Switch to list tab
            document.querySelector('.tab[data-tab="list"]').click();
            // Refresh event list
            document.getElementById('searchBtn').click();
        } catch (error) {
            showMessage(error.message, true);
        }
    });
}

export { initCreateEvent };
