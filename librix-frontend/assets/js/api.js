(function(window) {
    'use strict';

    let baseUrl = (window.LIBRIX_API_BASE_URL || window.API_BASE_URL || '').replace(/\/+$/, '');

    if (!baseUrl) {
        baseUrl = 'http://localhost:8000/index.php/api/v1';
    }

    const api = {
        baseUrl: baseUrl,

        request: function(endpoint, options) {
            options = options || {};

            const token = localStorage.getItem('librix_token');
            const headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };

            if (token) {
                headers['Authorization'] = 'Bearer ' + token;
            }

            if (options.headers) {
                for (const key in options.headers) {
                    headers[key] = options.headers[key];
                }
            }

            const url = api.baseUrl.replace(/\/+$/, '') + '/' + endpoint.replace(/^\/+/, '');

            return fetch(url, {
                method: options.method || 'GET',
                headers: headers,
                body: options.body
            }).then(function(response) {
                return response.text().then(function(text) {
                    let data = null;
                    if (text) {
                        try {
                            data = JSON.parse(text);
                        } catch (e) {
                            data = null;
                        }
                    }

                    if (response.ok) {
                        return data || { success: true, data: null };
                    }

                    if (response.status === 401) {
                        localStorage.removeItem('librix_token');
                        localStorage.removeItem('librix_user');
                        const loginPage = window.location.pathname.indexOf('/pages/') !== -1 ? 'login.html' : 'pages/login.html';
                        window.location.href = loginPage;
                        return new Promise(function() {});
                    }

                    const message = (data && (data.error || data.message)) || 'Request failed';
                    throw new Error(message);
                });
            });
        },

        get: function(endpoint, params) {
            params = params || {};

            const parts = [];
            for (const key in params) {
                const value = params[key];
                if (value !== undefined && value !== null && value !== '') {
                    parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
                }
            }

            let url = endpoint;
            if (parts.length > 0) {
                url += (endpoint.indexOf('?') !== -1 ? '&' : '?') + parts.join('&');
            }

            return api.request(url, { method: 'GET' });
        },

        post: function(endpoint, body) {
            return api.request(endpoint, {
                method: 'POST',
                body: JSON.stringify(body || {})
            });
        },

        put: function(endpoint, body) {
            return api.request(endpoint, {
                method: 'PUT',
                body: JSON.stringify(body || {})
            });
        },

        delete: function(endpoint, body) {
            return api.request(endpoint, {
                method: 'DELETE',
                body: JSON.stringify(body || {})
            });
        },

        librarian: {
            getStats: function(orgId) {
                return api.get('/librarian/stats', orgId ? { org_id: orgId } : {});
            },
            getBooks: function(params) {
                return api.get('/librarian/books', params);
            },
            addBook: function(bookData) {
                return api.post('/librarian/books', bookData);
            },
            deleteBook: function(bookId) {
                return api.delete('/librarian/books?id=' + bookId);
            },
            setOrg: function(orgId) {
                return api.post('/librarian/set_org', { org_id: orgId });
            }
        },

        organizations: {
            getAll: function() {
                return api.get('/organizations');
            },
            getById: function(id) {
                return api.get('/organizations?id=' + id);
            }
        },

        notifications: {
            getAll: function(params) {
                return api.get('/notifications', params);
            },
            markAsRead: function(id) {
                return api.post('/notifications/read', { id: id });
            },
            markAllAsRead: function() {
                return api.post('/notifications/read', { all: true });
            }
        },

        reservations: {
            getAll: function(params) {
                return api.get('/admin/reservations', params);
            },
            updateStatus: function(id, status) {
                return api.put('/admin/reservations', { id: id, status: status });
            }
        },

        fines: {
            getAll: function(params) {
                return api.get('/admin/fines', params);
            },
            updateStatus: function(id, status) {
                return api.put('/admin/fines', { id: id, status: status });
            }
        }
    };

    window.api = api;

})(window);
