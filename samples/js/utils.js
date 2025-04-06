// Show message
function showMessage(text, isError = false) {
    const msgDiv = document.getElementById('message');
    msgDiv.textContent = text;
    msgDiv.className = 'message ' + (isError ? 'error' : 'success');
    msgDiv.style.display = 'block';
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        msgDiv.style.display = 'none';
    }, 5000);
}

// Initialize tabs
function initTabs() {
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', () => {
            // Skip if not authenticated and trying to access other tabs
            const authToken = localStorage.getItem('authToken');
            if (!authToken && tab.dataset.tab !== 'auth') {
                showMessage('Please log in first', true);
                return;
            }
            
            // Activate clicked tab
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            
            // Show corresponding content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(tab.dataset.tab).classList.add('active');
        });
    });
}

export { showMessage, initTabs };
