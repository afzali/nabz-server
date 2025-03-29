// API endpoint constants
// Make sure the base URL is correct for accessing the API from the public directory
const API_BASE_URL = '/nabz-server';
const API_ENDPOINTS = {
    REGISTER: `${API_BASE_URL}/register`,
    LOGIN: `${API_BASE_URL}/login`,
    EVENTS: `${API_BASE_URL}/events`
};

// Send API request
async function sendRequest(url, method = 'GET', data = null) {
    // Add console logging to debug the URL
    console.log('Sending request to:', url);
    
    const headers = {
        'Content-Type': 'application/json'
    };
    
    // Add authorization header if token exists
    const authToken = localStorage.getItem('authToken');
    if (authToken) {
        headers['Authorization'] = `Bearer ${authToken}`;
    }
    
    const options = {
        method,
        headers
    };
    
    if (data && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {
        options.body = JSON.stringify(data);
    }
    
    try {
        console.log('Request options:', options);
        const response = await fetch(url, options);
        console.log('Response status:', response.status);
        const result = await response.json();
        console.log('Response data:', result);
        
        if (!result.success && response.status !== 200) {
            throw new Error(result.message || 'Request failed');
        }
        
        return result;
    } catch (error) {
        console.error('API request error:', error);
        throw error;
    }
}

export { API_ENDPOINTS, sendRequest };
