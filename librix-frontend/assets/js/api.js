/**
 * LibriX API Client
 * Real HTTP REST Client with seamless multi-tenant RBAC support
 */

(function(window) {
    'use strict';

    // -------------------------------------------------------------
    // 1. Storage Helpers
    // -------------------------------------------------------------
    const db = {
        get: (key, fallback) => {
            try {
                const data = localStorage.getItem('librix_' + key);
                return data ? JSON.parse(data) : fallback;
            } catch (e) {
                return fallback;
            }
        },
        set: (key, val) => {
            try {
                localStorage.setItem('librix_' + key, JSON.stringify(val));
            } catch (e) {}
        }
    };

    // -------------------------------------------------------------
    // 2. API Base URL Configuration
    // -------------------------------------------------------------
    const API_BASE_URL = (() => {
        const configuredBase = (window.LIBRIX_API_BASE_URL || window.API_BASE_URL || '').replace(/\/+$/, '');
        if (configuredBase) return configuredBase;

        const origin = window.location && window.location.origin ? window.location.origin : '';
        if (origin) {
            return `${origin}/librix-backend/index.php/api/v1`;
        }

        return '/librix-backend/index.php/api/v1';
    })();

    // -------------------------------------------------------------
    // 3. API Client
    // -------------------------------------------------------------
    const api = {
        baseUrl: API_BASE_URL,

        request: async function(endpoint, options = {}) {
            const token = localStorage.getItem('librix_token');
            const headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                ...(options.headers || {})
            };

            const url = `${api.baseUrl.replace(/\/+$/, '')}/${endpoint.replace(/^\/+/, '')}`;

            try {
                const response = await fetch(url, {
                    ...options,
                    headers
                });

                if (response.ok) {
                    const text = await response.text();
                    return text ? JSON.parse(text) : { success: true, data: null };
                }

                const errorText = await response.text().catch(() => '');
                let errJson = {};
                if (errorText) {
                    try { errJson = JSON.parse(errorText); } catch (e) { errJson = {}; }
                }

                if (response.status === 401 || response.status === 403) {
                    throw new Error(errJson.error || errJson.message || 'Authorization failed');
                }

                throw new Error(errJson.error || errJson.message || 'Request failed');

            } catch (networkError) {
                throw networkError;
            }
        },

        get: function(endpoint, params = {}) {
            let queryString = '';
            if (Object.keys(params).length > 0) {
                const searchParams = new URLSearchParams();
                for (const [k, v] of Object.entries(params)) {
                    if (v !== undefined && v !== null && v !== '') {
                        searchParams.append(k, v);
                    }
                }
                const qs = searchParams.toString();
                if (qs) queryString = (endpoint.includes('?') ? '&' : '?') + qs;
            }
            return this.request(endpoint + queryString, { method: 'GET' });
        },

        post: function(endpoint, body = {}) {
            return this.request(endpoint, {
                method: 'POST',
                body: JSON.stringify(body)
            });
        },

        put: function(endpoint, body = {}) {
            return this.request(endpoint, {
                method: 'PUT',
                body: JSON.stringify(body)
            });
        },

        delete: function(endpoint, body = {}) {
            return this.request(endpoint, {
                method: 'DELETE',
                body: JSON.stringify(body)
            });
        },

        // --- Specialized Module Namespaces ---

        librarian: {
            getStats: (orgId) => api.get('/librarian/stats', orgId ? { org_id: orgId } : {}),
            getBooks: (params) => api.get('/librarian/books', params),
            addBook: (bookData) => api.post('/librarian/books', bookData),
            deleteBook: (bookId) => api.delete(`/librarian/books?id=${bookId}`),
            setOrg: (orgId) => api.post('/librarian/set_org', { org_id: orgId })
        },

        organizations: {
            getAll: () => api.get('/organizations'),
            getById: (id) => api.get(`/organizations?id=${id}`)
        },

        notifications: {
            getAll: (params) => api.get('/notifications', params),
            markAsRead: (id) => api.post('/notifications/read', { id }),
            markAllAsRead: () => api.post('/notifications/read', { all: true })
        },

        reservations: {
            getAll: (params) => api.get('/admin/reservations', params),
            updateStatus: (id, status) => api.put('/admin/reservations', { id, status })
        },

        fines: {
            getAll: (params) => api.get('/admin/fines', params),
            updateStatus: (id, status) => api.put('/admin/fines', { id, status })
        }
    };

    window.api = api;

})(window);
