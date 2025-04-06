import { API_ENDPOINTS, sendRequest } from './api.js';
import { showMessage } from './utils.js';

// Authentication token and user info
let authToken = localStorage.getItem('authToken');
let currentUser = localStorage.getItem('username');

// Update auth status display
function updateAuthStatus() {
    const statusText = document.getElementById('statusText');
    if (authToken) {
        statusText.textContent = `Logged in as ${currentUser}`;
        document.querySelectorAll('.tab').forEach(tab => {
            tab.style.display = 'block';
        });
    } else {
        statusText.textContent = 'Not logged in';
        document.querySelectorAll('.tab:not([data-tab="auth"])').forEach(tab => {
            tab.style.display = 'none';
        });
    }
}

// Initialize auth
function initAuth() {
    // Login
    document.getElementById('loginBtn').addEventListener('click', async () => {
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        
        if (!username || !password) {
            showMessage('Username and password are required', true);
            return;
        }
        
        try {
            const result = await sendRequest(API_ENDPOINTS.LOGIN, 'POST', {
                username,
                password
            });
            
            showMessage('Login successful!');
            
            // Store auth token from server response
            authToken = result.user.token;
            currentUser = result.user.username;
            localStorage.setItem('authToken', authToken);
            localStorage.setItem('username', currentUser);
            
            updateAuthStatus();
            
            // Clear form
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
        } catch (error) {
            showMessage(error.message, true);
        }
    });
    
    // Register
    document.getElementById('registerBtn').addEventListener('click', async () => {
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        
        if (!username || !password) {
            showMessage('Username and password are required', true);
            return;
        }
        
        try {
            const result = await sendRequest(API_ENDPOINTS.REGISTER, 'POST', {
                username,
                password
            });
            
            showMessage('Registration successful! You can now log in.');
            
            // Clear form
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
        } catch (error) {
            showMessage(error.message, true);
        }
    });
}

export { authToken, currentUser, updateAuthStatus, initAuth };
