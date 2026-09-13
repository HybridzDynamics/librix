/**
 * LibriX API Client
 * Centralized HTTP request handling with auto-discovery, auto-fallback, and Bearer token injection
 */

const LibrixConfig = {
    // Candidate backend URLs in order of priority
    getCandidates: function() {
        const candidates = [];
        const stored = localStorage.getItem('librix_api_url');
        if (stored) {
            candidates.push(stored.replace(/\/$/, ''));
        }

        // Primary dev server (PHP built-in: php -S localhost:8000)
        candidates.push('http://localhost:8000/api/v1');

        const origin = window.location.origin;
        const pathname = window.location.pathname;

        if (window.location.protocol !== 'file:') {
            if (pathname.includes('/librix/')) {
                candidates.push(`${origin}/librix/librix-backend/api/v1`);
            }
            if (pathname.includes('/librix-frontend/')) {
                candidates.push(`${origin}/librix-backend/api/v1`);
            }
            candidates.push(`${origin}/api/v1`);
            candidates.push(`${origin}/librix/librix-backend/api/v1`);
            candidates.push(`${origin}/librix-backend/api/v1`);
        }

        // Deduplicate
        return [...new Set(candidates)];
    },

    getBaseUrl: function() {
        const stored = localStorage.getItem('librix_api_url');
        if (stored) return stored;
        const list = this.getCandidates();
        return list[0] || 'http://localhost/librix-backend/api/v1';
    },

    setBaseUrl: function(url) {
        localStorage.setItem('librix_api_url', url.replace(/\/$/, ''));
    }
};

const api = {
    /**
     * Core request runner with automatic multi-URL fallback discovery
     */
    request: async function(endpoint, options = {}) {
        const cleanEndpoint = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
        const candidates = LibrixConfig.getCandidates();

        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...(options.headers || {})
        };

        const token = localStorage.getItem('librix_token');
        if (token && !headers['Authorization']) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        const config = {
            method: (options.method || 'GET').toUpperCase(),
            headers: headers
        };

        if (options.body && config.method !== 'GET') {
            config.body = typeof options.body === 'string' ? options.body : JSON.stringify(options.body);
        }

        let lastError = null;

        // Try candidates
        for (let i = 0; i < candidates.length; i++) {
            const baseUrl = candidates[i];
            const url = `${baseUrl}${cleanEndpoint}`;

            try {
                const response = await fetch(url, config);
                
                // If we got a response (even 4xx/5xx), this server exists!
                if (i > 0 && response.status !== 404) {
                    LibrixConfig.setBaseUrl(baseUrl);
                }

                const data = await response.json().catch(() => ({
                    success: false,
                    error: `HTTP ${response.status}: ${response.statusText}`
                }));

                // Handle session expiration
                if (response.status === 401 && token && !endpoint.includes('/auth/login')) {
                    localStorage.removeItem('librix_token');
                    localStorage.removeItem('librix_user');
                    window.dispatchEvent(new CustomEvent('librix:auth-expired'));
                }

                if (!response.ok || data.success === false) {
                    const error = new Error(data.error || data.message || `Request failed (${response.status})`);
                    error.status = response.status;
                    error.errors = data.errors || null;
                    error.data = data;
                    throw error;
                }

                return data;

            } catch (err) {
                // If it's a network error (Failed to fetch), continue loop to next candidate
                if (err.name === 'TypeError' && err.message.includes('fetch')) {
                    lastError = err;
                    continue;
                }
                // If it was an intentional HTTP error response from server, throw it directly
                throw err;
            }
        }

        console.error(`[LibriX API] Failed to reach backend on candidate paths:`, candidates);
        throw (lastError || new Error("Unable to connect to LibriX backend server. Please verify your local web server is running."));
    },

    get: function(endpoint, queryParams = {}) {
        let url = endpoint;
        const keys = Object.keys(queryParams);
        if (keys.length > 0) {
            const params = new URLSearchParams();
            keys.forEach(k => {
                if (queryParams[k] !== undefined && queryParams[k] !== null && queryParams[k] !== '') {
                    params.append(k, queryParams[k]);
                }
            });
            const qs = params.toString();
            if (qs) {
                url += (url.includes('?') ? '&' : '?') + qs;
            }
        }
        return this.request(url, { method: 'GET' });
    },

    post: function(endpoint, body = {}) {
        return this.request(endpoint, { method: 'POST', body: body });
    },

    put: function(endpoint, body = {}) {
        return this.request(endpoint, { method: 'PUT', body: body });
    },

    delete: function(endpoint, body = null) {
        return this.request(endpoint, { method: 'DELETE', body: body });
    }
};

window.LibrixConfig = LibrixConfig;
window.api = api;
