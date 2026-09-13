/**
 * LibriX Authentication Module
 * Manages user sessions, auth state, role checks, and dynamic navigation updates with Lucide icons
 */

const auth = {
    /**
     * Get stored authentication token
     */
    getToken: function() {
        return localStorage.getItem('librix_token');
    },

    /**
     * Get stored user profile
     */
    getUser: function() {
        const userStr = localStorage.getItem('librix_user');
        if (!userStr) return null;
        try {
            return JSON.parse(userStr);
        } catch (e) {
            return null;
        }
    },

    /**
     * Check if user is logged in
     */
    isLoggedIn: function() {
        return !!this.getToken() && !!this.getUser();
    },

    /**
     * Check if current user is an admin
     */
    isAdmin: function() {
        const user = this.getUser();
        return user && user.role === 'admin';
    },

    /**
     * Authenticate with email and password
     */
    login: async function(email, password) {
        try {
            const response = await api.post('/auth/login', { email, password });
            if (response.success && response.data) {
                localStorage.setItem('librix_token', response.data.token);
                localStorage.setItem('librix_user', JSON.stringify(response.data.user));
                return response.data;
            }
            throw new Error(response.message || 'Login failed');
        } catch (error) {
            throw error;
        }
    },

    /**
     * Register a new user
     */
    register: async function(name, email, password) {
        try {
            const response = await api.post('/auth/register', { name, email, password });
            if (response.success && response.data) {
                localStorage.setItem('librix_token', response.data.token);
                localStorage.setItem('librix_user', JSON.stringify(response.data.user));
                return response.data;
            }
            throw new Error(response.message || 'Registration failed');
        } catch (error) {
            throw error;
        }
    },

    /**
     * Log out current session
     */
    logout: async function() {
        const token = this.getToken();
        if (token) {
            try {
                await api.post('/auth/logout');
            } catch (e) {
                // Ignore API failure on logout
            }
        }
        localStorage.removeItem('librix_token');
        localStorage.removeItem('librix_user');
        
        // Redirect to login or home
        const isRoot = window.location.pathname.endsWith('index.html') || window.location.pathname.endsWith('/');
        if (isRoot) {
            window.location.reload();
        } else {
            const isInPages = window.location.pathname.includes('/pages/');
            const isInSub = window.location.pathname.includes('/user/') || window.location.pathname.includes('/admin/');
            const target = isInSub ? '../login.html' : (isInPages ? 'login.html' : 'pages/login.html');
            window.location.href = target;
        }
    },

    /**
     * Require authentication on protected pages
     */
    requireAuth: function(redirectUrl = null) {
        if (!this.isLoggedIn()) {
            const current = encodeURIComponent(window.location.href);
            const isInPages = window.location.pathname.includes('/pages/');
            const isInSub = window.location.pathname.includes('/user/') || window.location.pathname.includes('/admin/');
            const defaultRedirect = isInSub ? `../login.html?redirect=${current}` : (isInPages ? `login.html?redirect=${current}` : `pages/login.html?redirect=${current}`);
            window.location.href = redirectUrl || defaultRedirect;
            return false;
        }
        return true;
    },

    /**
     * Require admin access
     */
    requireAdmin: function(redirectUrl = null) {
        if (!this.requireAuth()) return false;
        if (!this.isAdmin()) {
            alert('Access Denied: Administrator privileges are required.');
            const isInPages = window.location.pathname.includes('/pages/');
            const isInSub = window.location.pathname.includes('/user/') || window.location.pathname.includes('/admin/');
            window.location.href = isInSub ? '../user/dashboard.html' : (isInPages ? 'user/dashboard.html' : 'pages/user/dashboard.html');
            return false;
        }
        return true;
    },

    /**
     * Dynamically update navigation bar links based on auth state
     */
    updateNav: function() {
        const navAuth = document.querySelector('.nav-auth') || document.getElementById('nav-auth-container');
        if (!navAuth) return;

        const isInPages = window.location.pathname.includes('/pages/');
        const isInSub = window.location.pathname.includes('/user/') || window.location.pathname.includes('/admin/');
        
        const pathPrefix = isInSub ? '../' : (isInPages ? '' : 'pages/');
        const userIcon = window.lucide ? lucide.render('user', { size: 14 }) : '';

        if (this.isLoggedIn()) {
            const user = this.getUser();
            const isAdmin = this.isAdmin();
            const dashboardUrl = isAdmin ? `${pathPrefix}admin/dashboard.html` : `${pathPrefix}user/dashboard.html`;

            navAuth.innerHTML = `
                <div style="display: flex; align-items: center; gap: 12px;">
                    <a href="${dashboardUrl}" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                        ${userIcon}
                        <span>${utils.escapeHtml(user.name.split(' ')[0])}</span>
                        ${isAdmin ? '<span class="badge badge-primary" style="font-size: 10px; padding: 1px 6px;">ADMIN</span>' : ''}
                    </a>
                    <button id="nav-logout-btn" class="btn btn-outline btn-sm" style="border-color: var(--border);">Logout</button>
                </div>
            `;

            const logoutBtn = document.getElementById('nav-logout-btn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (confirm('Are you sure you want to log out?')) {
                        auth.logout();
                    }
                });
            }
        } else {
            navAuth.innerHTML = `
                <a href="${pathPrefix}login.html" class="btn btn-outline btn-sm">Login</a>
                <a href="${pathPrefix}register.html" class="btn btn-primary btn-sm">Sign Up</a>
            `;
        }

        if (window.lucide) {
            lucide.createIcons();
        }
    }
};

// Automatic listener for session expiry
window.addEventListener('librix:auth-expired', () => {
    if (typeof utils !== 'undefined') {
        utils.toast('Session Expired', 'Please log in again to continue.', 'warning');
    }
    setTimeout(() => {
        auth.logout();
    }, 1500);
});

// Auto-run updateNav on DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    auth.updateNav();
});

window.auth = auth;
