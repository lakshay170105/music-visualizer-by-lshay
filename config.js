/**
 * L-SHAY Music Visualizer - Frontend API Configuration
 * Easy-to-use API helper for frontend integration
 * Version: 1.0.0
 */

// API Configuration

const LSHAY_CONFIG = {
    // API Base URL - Multiple fallback methods for better compatibility
    API_URL: (function() {
        try {
            // Method 1: Try to detect current domain
            if (typeof window !== 'undefined' && window.location) {
                const protocol = window.location.protocol;
                const host = window.location.host;
                const pathname = window.location.pathname;
                
                // Extract directory path (remove filename if present)
                let directory = pathname;
                if (pathname.includes('.html') || pathname.includes('.php')) {
                    directory = pathname.substring(0, pathname.lastIndexOf('/') + 1);
                } else if (!pathname.endsWith('/')) {
                    directory = pathname + '/';
                }
                
                const fullUrl = `${protocol}//${host}${directory}api.php`;
                console.log('🔗 API URL detected:', fullUrl);
                return fullUrl;
            }
        } catch (error) {
            console.warn('⚠️ API URL detection failed:', error);
        }
        
        // Method 2: Fallback to relative path
        console.log('🔗 Using fallback API URL: ./api.php');
        return './api.php';
    })(),
    
    // Local Storage Keys
    STORAGE_KEYS: {
        AUTH_TOKEN: 'authToken',
        CURRENT_USER: 'currentUser',
        USER_DATA: 'userData'
    },
    
    // Session Settings
    SESSION: {
        EXPIRES_DAYS: 30,
        AUTO_REFRESH: true
    },
    
    // Debug Mode
    DEBUG: true
};

// API Helper Class

class LSHAY_API {
    
    /**
     * Make API Request with better error handling
     */
    static async request(action, method = 'GET', data = null) {
        try {
            // First, try to check if API file exists
            if (action === 'test_connection') {
                // Special handling for connection test
                const testUrl = LSHAY_CONFIG.API_URL.replace('api.php', 'test_connection.php');
                try {
                    const testResponse = await fetch(testUrl);
                    if (testResponse.ok) {
                        return await testResponse.json();
                    }
                } catch (testError) {
                    console.warn('⚠️ test_connection.php not found, trying api.php');
                }
            }
            
            const url = `${LSHAY_CONFIG.API_URL}?action=${action}`;
            
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                }
            };
            
            // Add authorization token if available
            const token = this.getToken();
            if (token) {
                options.headers['Authorization'] = token;
            }
            
            // Add body for POST requests
            if (method === 'POST' && data) {
                if (data instanceof FormData) {
                    // Convert FormData to URLSearchParams for better PHP compatibility
                    const params = new URLSearchParams();
                    for (const [key, value] of data.entries()) {
                        params.append(key, value);
                    }
                    options.body = params;
                } else {
                    // Create URLSearchParams from object
                    const params = new URLSearchParams();
                    Object.keys(data).forEach(key => {
                        params.append(key, data[key]);
                    });
                    options.body = params;
                }
                // Remove content-type header to let browser set it with boundary for FormData
                if (data instanceof FormData) {
                    delete options.headers['Content-Type'];
                }
            }
            
            if (LSHAY_CONFIG.DEBUG) {
                console.log(`🔄 API Request: ${action}`, { method, data, url });
            }
            
            const response = await fetch(url, options);
            
            // Check if response is ok
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const textResponse = await response.text();
                console.error('❌ Non-JSON response:', textResponse);
                throw new Error('Server returned non-JSON response. Check if API file exists and is properly configured.');
            }
            
            const result = await response.json();
            
            if (LSHAY_CONFIG.DEBUG) {
                console.log(`✅ API Response: ${action}`, result);
            }
            
            if (!result.success && result.message) {
                throw new Error(result.message);
            }
            
            return result;
            
        } catch (error) {
            if (LSHAY_CONFIG.DEBUG) {
                console.error(`❌ API Error: ${action}`, error);
            }
            
            // Provide more helpful error messages
            if (error.message.includes('Failed to fetch')) {
                throw new Error('Cannot connect to server. Please check if the website is running on a web server and api.php file exists.');
            } else if (error.message.includes('404')) {
                throw new Error('API file not found. Please make sure api.php exists in the same directory as index.html.');
            } else if (error.message.includes('500')) {
                throw new Error('Server error. Please check server logs and database configuration.');
            }
            
            throw error;
        }
    }
    
    /**
     * User Registration
     */
    static async register(username, email, password) {
        const result = await this.request('register', 'POST', {
            username,
            email,
            password
        });
        
        // Store token and user data
        this.setToken(result.data.token);
        this.setCurrentUser(result.data.username);
        this.setUserData(result.data);
        
        return result;
    }
    
    /**
     * User Login
     */
    static async login(username, password) {
        const result = await this.request('login', 'POST', {
            username,
            password
        });
        
        // Store token and user data
        this.setToken(result.data.token);
        this.setCurrentUser(result.data.user.username);
        this.setUserData(result.data.user);
        
        return result;
    }
    
    /**
     * User Logout
     */
    static async logout() {
        try {
            await this.request('logout', 'POST', {
                token: this.getToken()
            });
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            // Clear local storage
            this.clearAuth();
        }
    }
    
    /**
     * Get User Profile
     */
    static async getProfile() {
        return await this.request('profile', 'GET');
    }
    
    /**
     * Save Visualizer
     */
    static async saveVisualizer(name, settings, thumbnail = '') {
        return await this.request('save_visualizer', 'POST', {
            token: this.getToken(),
            name,
            settings: JSON.stringify(settings),
            thumbnail
        });
    }
    
    /**
     * Get User's Visualizers
     */
    static async getVisualizers(page = 1, limit = 20, search = '') {
        let url = `get_visualizers&token=${this.getToken()}&page=${page}&limit=${limit}`;
        if (search) {
            url += `&search=${encodeURIComponent(search)}`;
        }
        return await this.request(url, 'GET');
    }
    
    /**
     * Get Templates
     */
    static async getTemplates(page = 1, limit = 20, genre = '', style = '') {
        let url = `get_templates&page=${page}&limit=${limit}`;
        if (genre) url += `&genre=${genre}`;
        if (style) url += `&style=${style}`;
        return await this.request(url, 'GET');
    }
    
    /**
     * Get Active Sessions (Multi-Device)
     */
    static async getSessions() {
        return await this.request('get_sessions', 'GET');
    }
    
    /**
     * Revoke Session (Logout from specific device)
     */
    static async revokeSession(sessionId) {
        return await this.request('revoke_session', 'POST', {
            token: this.getToken(),
            session_id: sessionId
        });
    }
    
    /**
     * Sync State (Get latest data)
     */
    static async syncState() {
        return await this.request('sync_state', 'GET');
    }
    
    /**
     * Get Admin Dashboard Data
     */
    static async getAdminDashboard() {
        return await this.request('admin_dashboard', 'GET');
    }
    
    /**
     * Test API Connection
     */
    static async testConnection() {
        try {
            console.log('🔍 Testing API connection...');
            console.log('🔗 API URL:', LSHAY_CONFIG.API_URL);
            
            // Try multiple connection methods
            const testMethods = [
                // Method 1: Try test_connection.php
                async () => {
                    const testUrl = LSHAY_CONFIG.API_URL.replace('api.php', 'test_connection.php');
                    console.log('🔍 Testing with test_connection.php:', testUrl);
                    const response = await fetch(testUrl);
                    if (response.ok) {
                        return await response.json();
                    }
                    throw new Error(`HTTP ${response.status}`);
                },
                
                // Method 2: Try api.php with test_database action
                async () => {
                    console.log('🔍 Testing with api.php?action=test_database');
                    return await this.request('test_database', 'GET');
                },
                
                // Method 3: Try simple ping to api.php
                async () => {
                    console.log('🔍 Testing simple ping to api.php');
                    const response = await fetch(LSHAY_CONFIG.API_URL);
                    if (response.ok) {
                        const text = await response.text();
                        return { success: true, message: 'API file accessible', data: { response: text.substring(0, 100) } };
                    }
                    throw new Error(`HTTP ${response.status}`);
                }
            ];
            
            // Try each method
            for (let i = 0; i < testMethods.length; i++) {
                try {
                    const result = await testMethods[i]();
                    console.log(`✅ Connection test ${i + 1} successful:`, result);
                    return result;
                } catch (error) {
                    console.warn(`⚠️ Connection test ${i + 1} failed:`, error.message);
                    if (i === testMethods.length - 1) {
                        throw error; // Last method failed, throw error
                    }
                }
            }
            
        } catch (error) {
            console.error('❌ All connection tests failed:', error);
            
            // Provide helpful error message
            let helpfulMessage = 'Connection failed. ';
            if (error.message.includes('Failed to fetch')) {
                helpfulMessage += 'Please ensure:\n1. You are running this on a web server (not file://)\n2. The api.php file exists\n3. Your hosting allows PHP execution';
            } else if (error.message.includes('404')) {
                helpfulMessage += 'API file not found. Please upload api.php to your server.';
            } else if (error.message.includes('500')) {
                helpfulMessage += 'Server error. Please check your database configuration in api.php.';
            } else {
                helpfulMessage += error.message;
            }
            
            return {
                success: false,
                message: helpfulMessage,
                error: error.message
            };
        }
    }
    
    // Local storage helpers
    
    /**
     * Get Auth Token
     */
    static getToken() {
        return localStorage.getItem(LSHAY_CONFIG.STORAGE_KEYS.AUTH_TOKEN);
    }
    
    /**
     * Set Auth Token
     */
    static setToken(token) {
        localStorage.setItem(LSHAY_CONFIG.STORAGE_KEYS.AUTH_TOKEN, token);
    }
    
    /**
     * Get Current User
     */
    static getCurrentUser() {
        return localStorage.getItem(LSHAY_CONFIG.STORAGE_KEYS.CURRENT_USER);
    }
    
    /**
     * Set Current User
     */
    static setCurrentUser(username) {
        localStorage.setItem(LSHAY_CONFIG.STORAGE_KEYS.CURRENT_USER, username);
    }
    
    /**
     * Get User Data
     */
    static getUserData() {
        const data = localStorage.getItem(LSHAY_CONFIG.STORAGE_KEYS.USER_DATA);
        return data ? JSON.parse(data) : null;
    }
    
    /**
     * Set User Data
     */
    static setUserData(data) {
        localStorage.setItem(LSHAY_CONFIG.STORAGE_KEYS.USER_DATA, JSON.stringify(data));
    }
    
    /**
     * Check if User is Logged In
     */
    static isLoggedIn() {
        return !!this.getToken();
    }
    
    /**
     * Clear Authentication
     */
    static clearAuth() {
        localStorage.removeItem(LSHAY_CONFIG.STORAGE_KEYS.AUTH_TOKEN);
        localStorage.removeItem(LSHAY_CONFIG.STORAGE_KEYS.CURRENT_USER);
        localStorage.removeItem(LSHAY_CONFIG.STORAGE_KEYS.USER_DATA);
    }
}

// =====================================================
// AUTO-INITIALIZE
// =====================================================

// Check if user is logged in on page load
window.addEventListener('DOMContentLoaded', async () => {
    console.log('🚀 L-SHAY API initializing...');
    
    // Test API connection first
    try {
        const connectionTest = await LSHAY_API.testConnection();
        if (connectionTest.success) {
            console.log('✅ API connection successful');
        } else {
            console.error('❌ API connection failed:', connectionTest.message);
            // Show user-friendly error message
            showConnectionError(connectionTest.message);
        }
    } catch (error) {
        console.error('❌ API connection test failed:', error);
        showConnectionError('Cannot connect to server. Please check if you are running this on a web server and api.php exists.');
    }
    
    // Check if user is logged in
    if (LSHAY_API.isLoggedIn()) {
        console.log('✅ User logged in:', LSHAY_API.getCurrentUser());
        
        // Verify token is still valid
        try {
            await LSHAY_API.getProfile();
            console.log('✅ Session valid');
        } catch (error) {
            console.log('❌ Session expired, clearing auth');
            LSHAY_API.clearAuth();
        }
    } else {
        console.log('ℹ️ User not logged in');
    }
});

// Function to show connection error to user
function showConnectionError(message) {
    // Create error notification
    const errorDiv = document.createElement('div');
    errorDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        left: 20px;
        z-index: 10000;
        background: linear-gradient(135deg, #ff6b6b, #ee5a52);
        color: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(255, 107, 107, 0.3);
        font-family: 'Poppins', sans-serif;
        font-size: 14px;
        line-height: 1.5;
        max-width: 500px;
        margin: 0 auto;
    `;
    
    errorDiv.innerHTML = `
        <div style="display: flex; align-items: center; margin-bottom: 10px;">
            <span style="font-size: 20px; margin-right: 10px;">⚠️</span>
            <strong>Connection Error</strong>
        </div>
        <div style="margin-bottom: 15px;">${message}</div>
        <button onclick="this.parentElement.remove()" style="
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
        ">Close</button>
    `;
    
    document.body.appendChild(errorDiv);
    
    // Auto-remove after 10 seconds
    setTimeout(() => {
        if (errorDiv.parentElement) {
            errorDiv.remove();
        }
    }, 10000);
}


// Make available globally
window.LSHAY_API = LSHAY_API;
window.LSHAY_CONFIG = LSHAY_CONFIG;

console.log('🎵 L-SHAY API Helper Loaded');
console.log('📚 Usage: LSHAY_API.login(username, password)');
console.log('📚 Check: LSHAY_API.isLoggedIn()');




const VISUALIZER_IMPLEMENTATIONS = {

// ============ NEW WAVEFORM EFFECTS ============

magneticWave: `
  ctx.beginPath();
  for (let i = 0; i < bufferLength; i++) {
    const x = i * barWidth;
    const pull = Math.sin(i * 0.1 + animationFrame * 0.05) * 30;
    const y = centerY + (dataArray[i] / 2 - 64) + pull;
    if (i === 0) ctx.moveTo(x, y);
    else ctx.lineTo(x, y);
  }
  ctx.stroke();
  break;`,

quantumWave: `
  for (let layer = 0; layer < 3; layer++) {
    ctx.globalAlpha = 0.4;
    const brightness = 200 + Math.sin((layer * 120 + animationFrame) * 0.01) * 55;
    ctx.strokeStyle = \`rgba(\${brightness}, \${brightness}, \${brightness}, 0.8)\`;
    ctx.beginPath();
    for (let i = 0; i < bufferLength; i++) {
      const x = i * barWidth;
      const quantum = Math.sin(i * 0.5 + animationFrame * 0.1 + layer) * 20;
      const y = centerY + (dataArray[i] / 3 - 42) + quantum;
      if (i === 0) ctx.moveTo(x, y);
      else ctx.lineTo(x, y);
    }
    ctx.stroke();
  }
  ctx.globalAlpha = 1;
  ctx.strokeStyle = visualizerColor;
  break;`,

laserWave: `
  ctx.shadowBlur = 25;
  ctx.shadowColor = visualizerColor;
  ctx.lineWidth = 4;
  ctx.beginPath();
  for (let i = 0; i < bufferLength; i++) {
    const x = i * barWidth;
    const y = centerY + (dataArray[i] / 2 - 64);
    if (i === 0) ctx.moveTo(x, y);
    else ctx.lineTo(x, y);
  }
  ctx.stroke();
  ctx.shadowBlur = 0;
  ctx.lineWidth = 3;
  break;`,

dimensionalWave: `
  for (let dim = 0; dim < 4; dim++) {
    ctx.globalAlpha = 0.3;
    const offset = dim * 10;
    ctx.beginPath();
    for (let i = 0; i < bufferLength; i++) {
      const x = i * barWidth + offset;
      const y = centerY + (dataArray[i] / 2 - 64) + offset;
      if (i === 0) ctx.moveTo(x, y);
      else ctx.lineTo(x, y);
    }
    ctx.stroke();
  }
  ctx.globalAlpha = 1;
  break;`,

cosmicWave: `
  ctx.shadowBlur = 20;
  for (let i = 0; i < bufferLength; i++) {
    const x = i * barWidth;
    const y = centerY + (dataArray[i] / 2 - 64);
    const brightness = 200 + Math.sin((i * 2 + animationFrame) * 0.01) * 55;
    ctx.strokeStyle = \`rgba(\${brightness}, \${brightness}, \${brightness}, 0.9)\`;
    ctx.shadowColor = ctx.strokeStyle;
    ctx.beginPath();
    ctx.moveTo(x - 2, y);
    ctx.lineTo(x + 2, y);
    ctx.stroke();
  }
  ctx.shadowBlur = 0;
  ctx.strokeStyle = visualizerColor;
  break;`,

echoWave: `
  for (let echo = 0; echo < 5; echo++) {
    ctx.globalAlpha = 1 - (echo / 5);
    const delay = echo * 5;
    ctx.beginPath();
    for (let i = 0; i < bufferLength - delay; i++) {
      const x = i * barWidth;
      const y = centerY + (dataArray[i + delay] / 2 - 64);
      if (i === 0) ctx.moveTo(x, y);
      else ctx.lineTo(x, y);
    }
    ctx.stroke();
  }
  ctx.globalAlpha = 1;
  break;`,

spiralWave: `
  ctx.beginPath();
  for (let i = 0; i < bufferLength; i++) {
    const angle = (i / bufferLength) * Math.PI * 6 + animationFrame * 0.02;
    const dist = 50 + i * 1.5 + dataArray[i] / 4;
    const x = centerX + Math.cos(angle) * dist;
    const y = centerY + Math.sin(angle) * dist;
    if (i === 0) ctx.moveTo(x, y);
    else ctx.lineTo(x, y);
  }
  ctx.stroke();
  break;`,

electricWave: `
  ctx.shadowBlur = 15;
  ctx.shadowColor = visualizerColor;
  ctx.lineWidth = 2;
  ctx.beginPath();
  for (let i = 0; i < bufferLength; i++) {
    const x = i * barWidth;
    const y = centerY + (dataArray[i] / 2 - 64) + (Math.random() - 0.5) * 20;
    if (i === 0) ctx.moveTo(x, y);
    else ctx.lineTo(x, y);
  }
  ctx.stroke();
  ctx.shadowBlur = 0;
  ctx.lineWidth = 3;
  break;`,

tornadoWave: `
  for (let i = 0; i < bufferLength; i++) {
    const angle = (i / bufferLength) * Math.PI * 8 + animationFrame * 0.05;
    const radius = 30 + i * 0.5 + dataArray[i] / 5;
    const height = (i / bufferLength) * canvas.height;
    const x = centerX + Math.cos(angle) * radius;
    const y = height;
    ctx.beginPath();
    ctx.arc(x, y, 2, 0, Math.PI * 2);
    ctx.fill();
  }
  break;`,

crystalWave: `
  ctx.lineWidth = 1;
  for (let i = 0; i < bufferLength; i += 5) {
    const x = i * barWidth;
    const y = centerY + (dataArray[i] / 2 - 64);
    const size = dataArray[i] / 20;
    ctx.save();
    ctx.translate(x, y);
    ctx.rotate(animationFrame * 0.02);
    ctx.beginPath();
    for (let j = 0; j < 6; j++) {
      const angle = (j / 6) * Math.PI * 2;
      const px = Math.cos(angle) * size;
      const py = Math.sin(angle) * size;
      if (j === 0) ctx.moveTo(px, py);
      else ctx.lineTo(px, py);
    }
    ctx.closePath();
    ctx.stroke();
    ctx.restore();
  }
  ctx.lineWidth = 3;
  break;`,

// ========== NEON/GLOW BARS (Very Popular) ==========

neonBars: `
  ctx.shadowBlur = 20;
  for (let i = 0; i < bufferLength; i++) {
    const barHeight = dataArray[i];
    const x = i * barWidth;
    const y = canvas.height - barHeight;
    const brightness = 200 + Math.sin((i / bufferLength) * Math.PI * 2) * 55;
    ctx.fillStyle = \`rgba(\${brightness}, \${brightness}, \${brightness}, 0.9)\`;
    ctx.shadowColor = ctx.fillStyle;
    ctx.fillRect(x, y, barWidth - 2, barHeight);
  }
  ctx.shadowBlur = 0;
  ctx.fillStyle = visualizerColor;
  break;`,

liquidBars: `
  for (let i = 0; i < bufferLength; i++) {
    const barHeight = dataArray[i];
    const x = i * barWidth;
    const wobble = Math.sin(i * 0.3 + animationFrame * 0.1) * 10;
    const y = canvas.height - barHeight + wobble;
    const gradient = ctx.createLinearGradient(x, y, x, canvas.height);
    gradient.addColorStop(0, visualizerColor);
    gradient.addColorStop(1, secondaryColor);
    ctx.fillStyle = gradient;
    ctx.fillRect(x, y, barWidth - 2, barHeight);
  }
  ctx.fillStyle = visualizerColor;
  break;`,

fireBars: `
  for (let i = 0; i < bufferLength; i++) {
    const barHeight = dataArray[i];
    const x = i * barWidth;
    const y = canvas.height - barHeight;
    const gradient = ctx.createLinearGradient(x, y, x, canvas.height);
    gradient.addColorStop(0, '#ffffff');
    gradient.addColorStop(0.5, '#f0f0f0');
    gradient.addColorStop(1, '#e8e8e8');
    ctx.fillStyle = gradient;
    ctx.shadowBlur = 15;
    ctx.shadowColor = '#ffffff';
    ctx.fillRect(x, y, barWidth - 2, barHeight);
  }
  ctx.shadowBlur = 0;
  ctx.fillStyle = visualizerColor;
  break;`,

iceBars: `
  for (let i = 0; i < bufferLength; i++) {
    const barHeight = dataArray[i];
    const x = i * barWidth;
    const y = canvas.height - barHeight;
    const gradient = ctx.createLinearGradient(x, y, x, canvas.height);
    gradient.addColorStop(0, '#ffffff');
    gradient.addColorStop(0.5, '#f5f5f5');
    gradient.addColorStop(1, '#e8e8e8');
    ctx.fillStyle = gradient;
    ctx.shadowBlur = 10;
    ctx.shadowColor = '#ffffff';
    ctx.fillRect(x, y, barWidth - 2, barHeight);
  }
  ctx.shadowBlur = 0;
  ctx.fillStyle = visualizerColor;
  break;`,

rainbowBars: `
  for (let i = 0; i < bufferLength; i++) {
    const barHeight = dataArray[i];
    const x = i * barWidth;
    const y = canvas.height - barHeight;
    const brightness = 200 + Math.sin(((i / bufferLength) * Math.PI * 2 + animationFrame * 0.01)) * 55;
    ctx.fillStyle = \`rgba(\${brightness}, \${brightness}, \${brightness}, 0.9)\`;
    ctx.fillRect(x, y, barWidth - 2, barHeight);
  }
  ctx.fillStyle = visualizerColor;
  break;`

};

// Export visualizer implementations
window.VISUALIZER_IMPLEMENTATIONS = VISUALIZER_IMPLEMENTATIONS;

// =====================================================
// LOGO EFFECTS DATA
// =====================================================
const LOGO_EFFECTS = {
  none: {
    name: 'None',
    css: {},
    keyframes: null
  },
  glow: {
    name: 'Glow',
    css: {
      filter: 'drop-shadow(0 0 20px currentColor)',
      opacity: '0.9'
    },
    keyframes: null
  },
  neonGlow: {
    name: 'Neon Glow',
    css: {
      filter: 'drop-shadow(0 0 30px #00ff88) drop-shadow(0 0 60px #00ff88)',
      opacity: '1'
    },
    keyframes: null
  },
  pulse: {
    name: 'Pulse',
    css: {
      animation: 'logoPulse 2s ease-in-out infinite'
    },
    keyframes: '@keyframes logoPulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }'
  },
  floating: {
    name: 'Floating',
    css: {
      animation: 'logoFloat 3s ease-in-out infinite'
    },
    keyframes: '@keyframes logoFloat { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-10px); } }'
  },
  electric: {
    name: 'Electric',
    css: {
      filter: 'drop-shadow(0 0 25px #0099ff) brightness(1.2)',
      animation: 'logoElectric 0.5s ease-in-out infinite alternate'
    },
    keyframes: '@keyframes logoElectric { 0% { filter: drop-shadow(0 0 25px #0099ff) brightness(1.2); } 100% { filter: drop-shadow(0 0 35px #0099ff) brightness(1.4); } }'
  },
  hologram: {
    name: 'Hologram',
    css: {
      filter: 'hue-rotate(180deg) saturate(1.5)',
      animation: 'logoHologram 4s linear infinite'
    },
    keyframes: '@keyframes logoHologram { 0% { filter: hue-rotate(0deg) saturate(1.5); } 100% { filter: hue-rotate(360deg) saturate(1.5); } }'
  },
  vintage: {
    name: 'Vintage',
    css: {
      filter: 'sepia(0.8) contrast(1.2) brightness(0.9)',
      opacity: '0.85'
    },
    keyframes: null
  }
};

// =====================================================
// TEXT EFFECTS DATA
// =====================================================
const TEXT_EFFECTS = {
  none: {
    name: 'None',
    css: {},
    canvas: 'ctx.fillText(text, x, y);'
  },
  shadow: {
    name: 'Drop Shadow',
    css: {
      textShadow: '3px 3px 6px rgba(0,0,0,0.8)'
    },
    canvas: 'ctx.shadowColor = "rgba(0,0,0,0.8)"; ctx.shadowBlur = 6; ctx.shadowOffsetX = 3; ctx.shadowOffsetY = 3; ctx.fillText(text, x, y);'
  },
  glow: {
    name: 'Glow',
    css: {
      textShadow: '0 0 20px currentColor'
    },
    canvas: 'ctx.shadowColor = ctx.fillStyle; ctx.shadowBlur = 20; ctx.fillText(text, x, y);'
  },
  neon: {
    name: 'Neon',
    css: {
      textShadow: '0 0 10px #00ff88, 0 0 20px #00ff88, 0 0 30px #00ff88'
    },
    canvas: 'ctx.shadowColor = "#00ff88"; ctx.shadowBlur = 30; ctx.strokeStyle = "#00ff88"; ctx.lineWidth = 2; ctx.strokeText(text, x, y); ctx.fillText(text, x, y);'
  },
  outerGlow: {
    name: 'Outer Glow',
    css: {
      textShadow: '0 0 5px currentColor, 0 0 10px currentColor, 0 0 15px currentColor'
    },
    canvas: 'for(let i = 0; i < 3; i++) { ctx.shadowColor = ctx.fillStyle; ctx.shadowBlur = 5 + (i * 5); ctx.fillText(text, x, y); }'
  },
  gradientFill: {
    name: 'Gradient Fill',
    css: {
      background: 'linear-gradient(45deg, #ff6b6b, #4ecdc4)',
      webkitBackgroundClip: 'text',
      webkitTextFillColor: 'transparent'
    },
    canvas: 'const gradient = ctx.createLinearGradient(x, y - fontSize, x, y); gradient.addColorStop(0, "#ff6b6b"); gradient.addColorStop(1, "#4ecdc4"); ctx.fillStyle = gradient; ctx.fillText(text, x, y);'
  },
  stroke: {
    name: 'Stroke',
    css: {
      webkitTextStroke: '2px currentColor',
      webkitTextFillColor: 'transparent'
    },
    canvas: 'ctx.strokeStyle = ctx.fillStyle; ctx.lineWidth = 2; ctx.strokeText(text, x, y);'
  },
  emboss: {
    name: 'Emboss',
    css: {
      textShadow: '1px 1px 0px #ccc, 2px 2px 0px #c9c9c9, 3px 3px 0px #bbb'
    },
    canvas: 'ctx.shadowColor = "#ccc"; ctx.shadowBlur = 0; ctx.shadowOffsetX = 1; ctx.shadowOffsetY = 1; ctx.fillText(text, x, y); ctx.shadowColor = "#bbb"; ctx.shadowOffsetX = 3; ctx.shadowOffsetY = 3; ctx.fillText(text, x, y);'
  },
  fire: {
    name: 'Fire',
    css: {
      background: 'linear-gradient(0deg, #ff4500, #ffa500, #ffff00)',
      webkitBackgroundClip: 'text',
      webkitTextFillColor: 'transparent'
    },
    canvas: 'const gradient = ctx.createLinearGradient(x, y, x, y - fontSize); gradient.addColorStop(0, "#ff4500"); gradient.addColorStop(0.5, "#ffa500"); gradient.addColorStop(1, "#ffff00"); ctx.fillStyle = gradient; ctx.fillText(text, x, y);'
  },
  chrome: {
    name: 'Chrome',
    css: {
      background: 'linear-gradient(0deg, #eee, #999, #eee)',
      webkitBackgroundClip: 'text',
      webkitTextFillColor: 'transparent'
    },
    canvas: 'const gradient = ctx.createLinearGradient(x, y, x, y - fontSize); gradient.addColorStop(0, "#eee"); gradient.addColorStop(0.5, "#999"); gradient.addColorStop(1, "#eee"); ctx.fillStyle = gradient; ctx.fillText(text, x, y);'
  }
};

// =====================================================
// BACKGROUND EFFECTS DATA
// =====================================================
const BACKGROUND_EFFECTS = {
  solid: {
    name: 'Solid Color',
    css: { background: 'var(--bg-color)' },
    canvas: 'ctx.fillStyle = bgColor; ctx.fillRect(0, 0, canvas.width, canvas.height);'
  },
  linearGradient: {
    name: 'Linear Gradient',
    css: { background: 'linear-gradient(135deg, var(--bg-color1), var(--bg-color2))' },
    canvas: 'const gradient = ctx.createLinearGradient(0, 0, canvas.width, canvas.height); gradient.addColorStop(0, bgColor1); gradient.addColorStop(1, bgColor2); ctx.fillStyle = gradient; ctx.fillRect(0, 0, canvas.width, canvas.height);'
  },
  radialGradient: {
    name: 'Radial Gradient',
    css: { background: 'radial-gradient(circle, var(--bg-color1), var(--bg-color2))' },
    canvas: 'const gradient = ctx.createRadialGradient(canvas.width/2, canvas.height/2, 0, canvas.width/2, canvas.height/2, Math.max(canvas.width, canvas.height)/2); gradient.addColorStop(0, bgColor1); gradient.addColorStop(1, bgColor2); ctx.fillStyle = gradient; ctx.fillRect(0, 0, canvas.width, canvas.height);'
  },
  stars: {
    name: 'Starfield',
    css: {},
    canvas: 'for(let i = 0; i < 100; i++) { const x = Math.random() * canvas.width; const y = Math.random() * canvas.height; const brightness = Math.random() * 0.8 + 0.2; ctx.fillStyle = `rgba(255, 255, 255, ${brightness})`; ctx.fillRect(x, y, 1, 1); }'
  },
  particles: {
    name: 'Particles',
    css: {},
    canvas: 'for(let i = 0; i < 50; i++) { const x = (Math.random() * canvas.width + animationFrame * 0.5) % canvas.width; const y = Math.random() * canvas.height; const size = Math.random() * 3 + 1; ctx.fillStyle = `rgba(16, 185, 129, ${Math.random() * 0.5 + 0.3})`; ctx.beginPath(); ctx.arc(x, y, size, 0, Math.PI * 2); ctx.fill(); }'
  },
  waves: {
    name: 'Waves',
    css: {},
    canvas: 'for(let i = 0; i < canvas.width; i += 10) { const y = canvas.height/2 + Math.sin((i + animationFrame) * 0.01) * 50; ctx.fillStyle = `rgba(16, 185, 129, 0.1)`; ctx.fillRect(i, y, 10, canvas.height - y); }'
  },
  grid: {
    name: 'Grid',
    css: {},
    canvas: 'ctx.strokeStyle = "rgba(255, 255, 255, 0.1)"; ctx.lineWidth = 1; for(let i = 0; i < canvas.width; i += 50) { ctx.beginPath(); ctx.moveTo(i, 0); ctx.lineTo(i, canvas.height); ctx.stroke(); } for(let i = 0; i < canvas.height; i += 50) { ctx.beginPath(); ctx.moveTo(0, i); ctx.lineTo(canvas.width, i); ctx.stroke(); }'
  },
  hexagon: {
    name: 'Hexagon Pattern',
    css: {},
    canvas: 'const size = 30; for(let x = 0; x < canvas.width + size; x += size * 1.5) { for(let y = 0; y < canvas.height + size; y += size * Math.sqrt(3)) { const offsetX = (y / (size * Math.sqrt(3))) % 2 === 0 ? 0 : size * 0.75; ctx.strokeStyle = "rgba(255, 255, 255, 0.1)"; ctx.beginPath(); for(let i = 0; i < 6; i++) { const angle = (i * Math.PI) / 3; const px = x + offsetX + Math.cos(angle) * size; const py = y + Math.sin(angle) * size; if(i === 0) ctx.moveTo(px, py); else ctx.lineTo(px, py); } ctx.closePath(); ctx.stroke(); } }'
  },
  plasma: {
    name: 'Plasma',
    css: {},
    canvas: 'for(let x = 0; x < canvas.width; x += 4) { for(let y = 0; y < canvas.height; y += 4) { const value = Math.sin(x * 0.01 + animationFrame * 0.02) + Math.sin(y * 0.01 + animationFrame * 0.03) + Math.sin((x + y) * 0.01 + animationFrame * 0.01); const color = Math.floor((value + 3) * 42.5); ctx.fillStyle = `hsl(${color}, 70%, 50%)`; ctx.fillRect(x, y, 4, 4); } }'
  },
  matrix: {
    name: 'Matrix Rain',
    css: {},
    canvas: 'ctx.fillStyle = "rgba(0, 255, 0, 0.1)"; ctx.font = "12px monospace"; for(let i = 0; i < 20; i++) { const x = i * 30; const y = (animationFrame * 2 + i * 50) % canvas.height; ctx.fillText(String.fromCharCode(0x30A0 + Math.random() * 96), x, y); }'
  },
  nebula: {
    name: 'Nebula',
    css: {},
    canvas: 'const gradient = ctx.createRadialGradient(canvas.width * 0.3, canvas.height * 0.3, 0, canvas.width * 0.7, canvas.height * 0.7, canvas.width); gradient.addColorStop(0, "rgba(138, 43, 226, 0.8)"); gradient.addColorStop(0.5, "rgba(75, 0, 130, 0.6)"); gradient.addColorStop(1, "rgba(25, 25, 112, 0.4)"); ctx.fillStyle = gradient; ctx.fillRect(0, 0, canvas.width, canvas.height);'
  },
  aurora: {
    name: 'Aurora',
    css: {},
    canvas: 'for(let i = 0; i < 5; i++) { const gradient = ctx.createLinearGradient(0, canvas.height * 0.3, 0, canvas.height * 0.7); gradient.addColorStop(0, `hsla(${120 + i * 60 + animationFrame * 0.5}, 70%, 50%, 0)`); gradient.addColorStop(0.5, `hsla(${120 + i * 60 + animationFrame * 0.5}, 70%, 50%, 0.3)`); gradient.addColorStop(1, `hsla(${120 + i * 60 + animationFrame * 0.5}, 70%, 50%, 0)`); ctx.fillStyle = gradient; ctx.fillRect(i * canvas.width / 5, 0, canvas.width / 5, canvas.height); }'
  }
};

// Export effects data
window.LOGO_EFFECTS = LOGO_EFFECTS;
window.TEXT_EFFECTS = TEXT_EFFECTS;
window.BACKGROUND_EFFECTS = BACKGROUND_EFFECTS;

console.log('🎨 Visualizer Implementations Data Loaded');
console.log('📊 Total Visualizers:', Object.keys(VISUALIZER_IMPLEMENTATIONS).length);
console.log('🎭 Total Logo Effects:', Object.keys(LOGO_EFFECTS).length);
console.log('✨ Total Text Effects:', Object.keys(TEXT_EFFECTS).length);
console.log('🌈 Total Background Effects:', Object.keys(BACKGROUND_EFFECTS).length);
console.log('🎵 L-SHAY Enhanced Effects System Ready!');
